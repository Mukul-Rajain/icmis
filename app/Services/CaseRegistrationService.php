<?php

namespace App\Services;

use App\Models\CaseLawyer;
use App\Models\CaseParty;
use App\Models\CaseType;
use App\Models\CourtCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CaseRegistrationService
 * ─────────────────────────────────────────────────────────────
 * Single entry point for case registration. Orchestrates:
 *   1. Case number generation
 *   2. Track classification (TrackClassifier)
 *   3. Party and lawyer creation
 *   4. Initial priority score (PriorityScorer)
 *   5. Expected disposal date computation
 *
 * Use this from controllers/Livewire instead of creating cases directly,
 * to ensure DCFM rules are always applied.
 * ─────────────────────────────────────────────────────────────
 */
class CaseRegistrationService
{
    public function __construct(
        private CaseNumberGenerator $numberGenerator,
        private TrackClassifier $classifier,
        private PriorityScorer $scorer,
    ) {}

    /**
     * Register a new case.
     *
     * @param  array  $data  Validated form data from controller
     * @return CourtCase
     */
    public function register(array $data): CourtCase
    {
        return DB::transaction(function () use ($data) {
            // Determine in-custody flag from parties data BEFORE classification
            $hasInCustody = collect($data['parties'] ?? [])
                ->contains(fn ($p) => !empty($p['is_in_custody']));

            $hasSeniorCitizen = collect($data['parties'] ?? [])
                ->contains(fn ($p) => !empty($p['is_senior_citizen']));

            // Classify into a track
            $track = $this->classifier->classify([
                'case_type_id' => $data['case_type_id'],
                'has_in_custody_accused' => $hasInCustody,
                'party_count' => count($data['parties'] ?? []),
            ]);

            // Compute expected disposal date
            $caseType = CaseType::findOrFail($data['case_type_id']);
            $filingDate = Carbon::parse($data['filing_date'] ?? Carbon::today());
            $expectedDisposal = $filingDate->copy()->addDays($caseType->typical_duration_days);

            // Create the case
            $case = CourtCase::create([
                'case_number' => $this->numberGenerator->next(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'case_type_id' => $data['case_type_id'],
                'court_id' => $data['court_id'],
                'filed_by_user_id' => $data['filed_by_user_id'] ?? null,
                'filing_date' => $filingDate,
                'track' => $track,
                'priority_score' => 0, // will be set by scorer below
                'expected_disposal_date' => $expectedDisposal,
                'current_stage' => 'registered',
                'status' => CourtCase::STATUS_ACTIVE,
                'has_interim_relief_pending' => $data['has_interim_relief_pending'] ?? false,
                'has_in_custody_accused' => $hasInCustody,
                'involves_senior_citizen' => $hasSeniorCitizen,
            ]);

            // Add parties
            foreach ($data['parties'] ?? [] as $partyData) {
                CaseParty::create([
                    'case_id' => $case->id,
                    'user_id' => $partyData['user_id'] ?? null,
                    'party_type' => $partyData['party_type'],
                    'name' => $partyData['name'],
                    'phone' => $partyData['phone'] ?? null,
                    'email' => $partyData['email'] ?? null,
                    'address' => $partyData['address'] ?? null,
                    'is_in_custody' => $partyData['is_in_custody'] ?? false,
                    'is_senior_citizen' => $partyData['is_senior_citizen'] ?? false,
                ]);
            }

            // Add lawyers
            foreach ($data['lawyers'] ?? [] as $lawyerData) {
                CaseLawyer::create([
                    'case_id' => $case->id,
                    'lawyer_id' => $lawyerData['lawyer_id'],
                    'representing_party_id' => $lawyerData['representing_party_id'] ?? null,
                    'role' => $lawyerData['role'] ?? 'lead',
                    'engaged_on' => $filingDate,
                    'is_active' => true,
                ]);
            }

            // Compute and persist initial priority score
            $case->load('caseType');
            $this->scorer->scoreAndPersist($case, 'system');

            return $case->fresh(['caseType', 'parties', 'lawyers', 'court']);
        });
    }
}
