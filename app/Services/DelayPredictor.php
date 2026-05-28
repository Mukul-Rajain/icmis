<?php

namespace App\Services;

use App\Models\CourtCase;
use Carbon\Carbon;

/**
 * DelayPredictor
 * ─────────────────────────────────────────────────────────────
 * Flags cases at risk of breaching their track timeline BEFORE
 * they actually do. Uses statistical thresholds rather than ML —
 * fully explainable and effective for the project's scope.
 *
 * Risk levels:
 *   • SAFE     — on track
 *   • WATCH    — 60-80% of expected timeline elapsed
 *   • AT_RISK  — 80-100% elapsed, or excessive adjournments
 *   • OVERDUE  — past expected disposal date
 * ─────────────────────────────────────────────────────────────
 */
class DelayPredictor
{
    public const RISK_SAFE = 'safe';
    public const RISK_WATCH = 'watch';
    public const RISK_AT_RISK = 'at_risk';
    public const RISK_OVERDUE = 'overdue';

    public function assess(CourtCase $case): array
    {
        if (! $case->expected_disposal_date || $case->status !== CourtCase::STATUS_ACTIVE) {
            return ['level' => self::RISK_SAFE, 'reasons' => ['Case not active or no disposal date set']];
        }

        $totalDays = $case->filing_date->diffInDays($case->expected_disposal_date);
        $elapsedDays = $case->filing_date->diffInDays(Carbon::today());
        $percentageElapsed = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 100;

        $reasons = [];
        $level = self::RISK_SAFE;

        // Time-based assessment
        if ($percentageElapsed >= 100) {
            $level = self::RISK_OVERDUE;
            $daysOver = $elapsedDays - $totalDays;
            $reasons[] = "Case is {$daysOver} days past expected disposal date";
        } elseif ($percentageElapsed >= 80) {
            $level = self::RISK_AT_RISK;
            $reasons[] = sprintf("%.0f%% of expected timeline elapsed", $percentageElapsed);
        } elseif ($percentageElapsed >= 60) {
            $level = self::RISK_WATCH;
            $reasons[] = sprintf("%.0f%% of expected timeline elapsed — needs monitoring", $percentageElapsed);
        }

        // Adjournment pattern check (can escalate the level)
        if ($case->adjournment_count >= 5 && $level === self::RISK_SAFE) {
            $level = self::RISK_WATCH;
            $reasons[] = "High adjournment count ({$case->adjournment_count}) signals stalling";
        } elseif ($case->adjournment_count >= 8 && in_array($level, [self::RISK_SAFE, self::RISK_WATCH])) {
            $level = self::RISK_AT_RISK;
            $reasons[] = "Excessive adjournments ({$case->adjournment_count}) — intervention needed";
        }

        // Stage-vs-time mismatch (e.g., still in 'registered' after 50% of timeline)
        if ($percentageElapsed > 50 && in_array($case->current_stage, ['registered', 'notice_issued'])) {
            if ($level === self::RISK_SAFE) $level = self::RISK_WATCH;
            $reasons[] = "Still in early stage '{$case->current_stage}' despite passing midpoint";
        }

        return [
            'level' => $level,
            'percentage_elapsed' => round($percentageElapsed, 1),
            'reasons' => $reasons,
        ];
    }

    /**
     * Find all active cases currently at risk.
     */
    public function findAtRiskCases()
    {
        return CourtCase::active()
            ->with('caseType')
            ->get()
            ->map(fn ($case) => array_merge($this->assess($case), ['case' => $case]))
            ->filter(fn ($a) => in_array($a['level'], [self::RISK_AT_RISK, self::RISK_OVERDUE]))
            ->values();
    }
}
