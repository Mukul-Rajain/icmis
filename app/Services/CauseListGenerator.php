<?php

namespace App\Services;

use App\Models\CauseList;
use App\Models\CauseListItem;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Judge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CauseListGenerator
 * ─────────────────────────────────────────────────────────────
 * Generates the daily cause list for a judge on a given date by:
 *   1. Finding all cases with hearings scheduled for that date
 *      and judge (or auto-listing high-priority cases)
 *   2. Ordering them by track (fast → standard → complex)
 *      and then by priority score (descending) within each track
 *   3. Detecting conflicts (same lawyer in two courts at once)
 *   4. Respecting judge's max_daily_cases capacity
 *   5. Allocating realistic time slots
 *
 * The result is saved as a CauseList with ordered CauseListItems,
 * and a snapshot of the priority score is preserved for audit.
 * ─────────────────────────────────────────────────────────────
 */
class CauseListGenerator
{
    // Court hours
    private const COURT_START_HOUR = 10;
    private const COURT_END_HOUR = 17;
    private const LUNCH_BREAK_HOUR = 13;
    private const LUNCH_BREAK_DURATION = 60; // minutes

    public function __construct(
        private PriorityScorer $scorer
    ) {}

    /**
     * Generate a cause list for a judge on a specific date.
     *
     * @return array  ['cause_list' => CauseList, 'conflicts' => array, 'skipped' => array]
     */
    public function generate(Judge $judge, Carbon $date, ?int $generatedByUserId = null): array
    {
        // Verify judge is available
        if (! $judge->isAvailableOn($date)) {
            throw new \RuntimeException(
                "Judge {$judge->user->name} is not available on {$date->toDateString()}"
            );
        }

        return DB::transaction(function () use ($judge, $date, $generatedByUserId) {
            // Delete any existing draft list for this date+judge
            CauseList::where('list_date', $date)
                ->where('judge_id', $judge->id)
                ->where('status', CauseList::STATUS_DRAFT)
                ->delete();

            // Step 1: Collect candidate cases
            $candidates = $this->collectCandidates($judge, $date);

            // Step 2: Order them (track precedence + priority score)
            $ordered = $this->orderCases($candidates);

            // Step 3: Detect lawyer conflicts across courts
            $conflicts = $this->detectConflicts($ordered, $date);

            // Step 4: Apply judge capacity
            $capacityLimit = $judge->max_daily_cases;
            $selected = $ordered->take($capacityLimit);
            $skipped = $ordered->slice($capacityLimit);

            // Step 5: Create the cause list and items
            $causeList = CauseList::create([
                'list_date' => $date,
                'court_id' => $judge->court_id,
                'judge_id' => $judge->id,
                'status' => CauseList::STATUS_DRAFT,
                'total_cases' => $selected->count(),
                'generated_by_user_id' => $generatedByUserId,
                'generated_at' => Carbon::now(),
            ]);

            $this->createItems($causeList, $selected);

            return [
                'cause_list' => $causeList->fresh(['items.case']),
                'conflicts' => $conflicts,
                'skipped' => $skipped->pluck('case_number')->toArray(),
            ];
        });
    }

    /**
     * Collect cases that should be considered for listing on this date.
     *
     * Two sources:
     *   a) Cases with a scheduled hearing on this exact date (must-list)
     *   b) Active cases needing listing whose next_hearing_date is null
     *      or overdue, sorted by priority — gap-fillers
     */
    private function collectCandidates(Judge $judge, Carbon $date): \Illuminate\Support\Collection
    {
        // a) Already-scheduled hearings on this date
        $scheduledCases = CourtCase::active()
            ->with(['caseType', 'lawyers.lawyer'])
            ->where('court_id', $judge->court_id)
            ->where('assigned_judge_id', $judge->id)
            ->whereHas('hearings', function ($q) use ($date) {
                $q->whereDate('scheduled_date', $date)
                    ->where('status', Hearing::STATUS_SCHEDULED);
            })
            ->get();

        // b) Gap-fillers: active cases without a scheduled hearing, ranked by score
        $gapFillers = CourtCase::active()
            ->with(['caseType', 'lawyers.lawyer'])
            ->where('court_id', $judge->court_id)
            ->where('assigned_judge_id', $judge->id)
            ->whereNotIn('id', $scheduledCases->pluck('id'))
            ->where(function ($q) use ($date) {
                $q->whereNull('next_hearing_date')
                    ->orWhereDate('next_hearing_date', '<=', $date);
            })
            ->orderByDesc('priority_score')
            ->limit(50)
            ->get();

        return $scheduledCases->concat($gapFillers);
    }

    /**
     * Order cases:
     *   1. By track (fast → standard → complex)
     *   2. Within each track, by priority_score descending
     */
    private function orderCases(\Illuminate\Support\Collection $cases): \Illuminate\Support\Collection
    {
        $trackOrder = ['fast' => 1, 'standard' => 2, 'complex' => 3];

        return $cases->sortBy([
            fn ($a, $b) => ($trackOrder[$a->track] ?? 99) <=> ($trackOrder[$b->track] ?? 99),
            fn ($a, $b) => $b->priority_score <=> $a->priority_score,
        ])->values();
    }

    /**
     * Detect cases where the same lawyer would be scheduled in two
     * different courts on the same date.
     *
     * Returns a list of conflict descriptions for the UI to show.
     */
    private function detectConflicts(\Illuminate\Support\Collection $cases, Carbon $date): array
    {
        $conflicts = [];
        $caseIds = $cases->pluck('id')->toArray();

        // Get all lawyers across these cases
        $lawyerCaseMap = [];
        foreach ($cases as $case) {
            foreach ($case->lawyers as $caseLawyer) {
                $lawyerCaseMap[$caseLawyer->lawyer_id][] = $case->case_number;
            }
        }

        // Check if any of those lawyers have hearings in OTHER courts the same day
        foreach ($lawyerCaseMap as $lawyerId => $caseNumbers) {
            $otherHearings = Hearing::where('scheduled_date', $date)
                ->whereHas('case', function ($q) use ($caseIds) {
                    $q->whereNotIn('id', $caseIds);
                })
                ->whereHas('case.lawyers', function ($q) use ($lawyerId) {
                    $q->where('lawyer_id', $lawyerId);
                })
                ->with('case', 'court')
                ->get();

            if ($otherHearings->isNotEmpty()) {
                $conflicts[] = [
                    'lawyer_id' => $lawyerId,
                    'cases_in_this_list' => $caseNumbers,
                    'conflicting_hearings' => $otherHearings->map(fn ($h) => [
                        'case_number' => $h->case->case_number,
                        'court' => $h->court->name,
                    ])->toArray(),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Create cause list items with time slots.
     */
    private function createItems(CauseList $causeList, \Illuminate\Support\Collection $cases): void
    {
        $currentTime = Carbon::createFromTime(self::COURT_START_HOUR, 0);
        $lunchStart = Carbon::createFromTime(self::LUNCH_BREAK_HOUR, 0);
        $lunchEnd = $lunchStart->copy()->addMinutes(self::LUNCH_BREAK_DURATION);

        $serial = 1;
        foreach ($cases as $case) {
            // Estimate duration based on stage
            $duration = $this->estimateDuration($case);

            // Skip lunch break if needed
            if ($currentTime->between($lunchStart, $lunchEnd)) {
                $currentTime = $lunchEnd->copy();
            }

            // Find any scheduled hearing for this case on this date
            $hearing = $case->hearings()
                ->whereDate('scheduled_date', $causeList->list_date)
                ->first();

            CauseListItem::create([
                'cause_list_id' => $causeList->id,
                'case_id' => $case->id,
                'hearing_id' => $hearing?->id,
                'serial_number' => $serial,
                'estimated_time_slot' => $currentTime->format('H:i:s'),
                'estimated_duration_minutes' => $duration,
                'priority_score_at_listing' => $case->priority_score,
                'track_at_listing' => $case->track,
            ]);

            $currentTime->addMinutes($duration);
            $serial++;
        }
    }

    /**
     * Estimate how long a hearing will take based on case stage.
     */
    private function estimateDuration(CourtCase $case): int
    {
        return match ($case->current_stage) {
            'arguments', 'judgment_reserved' => 45,
            'evidence' => 60,
            'reply_filed', 'notice_issued' => 20,
            default => 15,
        };
    }
}
