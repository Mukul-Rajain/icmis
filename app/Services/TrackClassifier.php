<?php

namespace App\Services;

use App\Models\CaseType;
use App\Models\CourtCase;

/**
 * TrackClassifier
 * ─────────────────────────────────────────────────────────────
 * Assigns one of three DCFM tracks to a case:
 *   • FAST     — needs disposal in weeks (bail, urgent injunctions)
 *   • STANDARD — typical civil/criminal matters (months)
 *   • COMPLEX  — multi-party, commercial, constitutional (longer)
 *
 * Logic (in priority order):
 *   1. Time-sensitive case types → FAST (e.g., bail, habeas corpus)
 *   2. Cases with in-custody accused → FAST (constitutional liberty)
 *   3. Constitutional / multi-party commercial → COMPLEX
 *   4. Otherwise, fall back to the case_type's default_track
 *
 * For final-year scope this rule-based design is the right balance:
 * explainable in viva, demonstrable in demo, extendable later.
 * ─────────────────────────────────────────────────────────────
 */
class TrackClassifier
{
    /**
     * Determine the track for a case based on its attributes.
     *
     * @param  array  $caseData  Case attributes (case_type_id, parties, flags, etc.)
     * @return string  One of: 'fast', 'standard', 'complex'
     */
    public function classify(array $caseData): string
    {
        $caseType = CaseType::findOrFail($caseData['case_type_id']);

        // Rule 1: Time-sensitive case types always go fast track
        if ($caseType->is_time_sensitive) {
            return CaseType::TRACK_FAST;
        }

        // Rule 2: If an accused is in custody, fast-track for liberty reasons
        if (!empty($caseData['has_in_custody_accused'])) {
            return CaseType::TRACK_FAST;
        }

        // Rule 3: Constitutional category goes complex regardless of default
        if ($caseType->category === 'constitutional') {
            return CaseType::TRACK_COMPLEX;
        }

        // Rule 4: Commercial cases with multiple parties go complex
        if ($caseType->category === 'commercial' && ($caseData['party_count'] ?? 0) > 4) {
            return CaseType::TRACK_COMPLEX;
        }

        // Rule 5: Otherwise use the default track defined for this case type
        return $caseType->default_track;
    }

    /**
     * Re-classify an existing case (e.g., when in-custody flag changes,
     * or new parties get added).
     */
    public function reclassify(CourtCase $case): string
    {
        return $this->classify([
            'case_type_id' => $case->case_type_id,
            'has_in_custody_accused' => $case->has_in_custody_accused,
            'party_count' => $case->parties()->count(),
        ]);
    }

    /**
     * Get a human-readable explanation of why this track was chosen.
     * Useful for the viva demo — show the system's reasoning.
     */
    public function explain(array $caseData): array
    {
        $caseType = CaseType::findOrFail($caseData['case_type_id']);
        $reasons = [];

        if ($caseType->is_time_sensitive) {
            $reasons[] = "Case type '{$caseType->name}' is time-sensitive (statutory urgency)";
        }
        if (!empty($caseData['has_in_custody_accused'])) {
            $reasons[] = "Accused is in custody — fast-tracked for constitutional liberty";
        }
        if ($caseType->category === 'constitutional') {
            $reasons[] = "Constitutional category — assigned complex track";
        }
        if ($caseType->category === 'commercial' && ($caseData['party_count'] ?? 0) > 4) {
            $reasons[] = "Commercial case with >4 parties — complex track";
        }

        if (empty($reasons)) {
            $reasons[] = "No special rules triggered — used default track '{$caseType->default_track}' for case type '{$caseType->name}'";
        }

        return [
            'track' => $this->classify($caseData),
            'reasons' => $reasons,
        ];
    }
}
