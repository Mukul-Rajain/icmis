<?php

namespace App\Http\Controllers;

use App\Models\Adjournment;
use App\Models\CourtCase;
use App\Services\DelayPredictor;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Main analytics dashboard with all key DCFM metrics.
     */
    public function index(DelayPredictor $predictor)
    {
        // KPI cards
        $kpis = [
            'total_active' => CourtCase::active()->count(),
            'disposed_last_30_days' => CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                ->where('disposed_on', '>=', Carbon::now()->subDays(30))
                ->count(),
            'overdue_cases' => CourtCase::overdue()->count(),
            'avg_age_active_days' => (int) CourtCase::active()
                ->selectRaw('AVG(DATEDIFF(NOW(), filing_date)) as avg_age')
                ->value('avg_age'),
        ];

        // Cases by track
        $byTrack = CourtCase::active()
            ->selectRaw('track, COUNT(*) as count')
            ->groupBy('track')
            ->pluck('count', 'track');

        // Average disposal time per track (the headline DCFM metric)
        $disposalByTrack = CourtCase::where('status', CourtCase::STATUS_DISPOSED)
            ->whereNotNull('disposed_on')
            ->selectRaw('track, AVG(DATEDIFF(disposed_on, filing_date)) as avg_days, COUNT(*) as count')
            ->groupBy('track')
            ->get()
            ->keyBy('track');

        // Pendency by case type (top 10)
        $pendencyByType = CourtCase::active()
            ->join('case_types', 'cases.case_type_id', '=', 'case_types.id')
            ->selectRaw('case_types.name, case_types.code, COUNT(*) as pending')
            ->groupBy('case_types.id', 'case_types.name', 'case_types.code')
            ->orderByDesc('pending')
            ->limit(10)
            ->get();

        // Stage distribution (where are cases stuck?)
        $byStage = CourtCase::active()
            ->selectRaw('current_stage, COUNT(*) as count')
            ->groupBy('current_stage')
            ->pluck('count', 'current_stage');

        // At-risk cases
        $atRiskCount = $predictor->findAtRiskCases()->count();

        return view('analytics.index', compact(
            'kpis', 'byTrack', 'disposalByTrack',
            'pendencyByType', 'byStage', 'atRiskCount'
        ));
    }

    /**
     * Disposal trends over time — for the line chart.
     * Returns JSON for AJAX/Chart.js.
     */
    public function disposalTrends()
    {
        $months = collect(range(11, 0))->map(function ($n) {
            $month = Carbon::now()->subMonths($n);
            return [
                'month' => $month->format('M Y'),
                'fast' => CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                    ->where('track', 'fast')
                    ->whereYear('disposed_on', $month->year)
                    ->whereMonth('disposed_on', $month->month)
                    ->count(),
                'standard' => CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                    ->where('track', 'standard')
                    ->whereYear('disposed_on', $month->year)
                    ->whereMonth('disposed_on', $month->month)
                    ->count(),
                'complex' => CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                    ->where('track', 'complex')
                    ->whereYear('disposed_on', $month->year)
                    ->whereMonth('disposed_on', $month->month)
                    ->count(),
            ];
        });

        return response()->json($months);
    }

    /**
     * Adjournment pattern analysis - reveals systemic delay causes.
     */
    public function adjournmentPatterns()
    {
        $byReason = Adjournment::where('granted', true)
            ->selectRaw('reason_category, COUNT(*) as count')
            ->groupBy('reason_category')
            ->orderByDesc('count')
            ->get();

        $byRequester = Adjournment::where('granted', true)
            ->selectRaw('requested_by_role, COUNT(*) as count')
            ->groupBy('requested_by_role')
            ->get();

        $highAdjournmentCases = CourtCase::active()
            ->where('adjournment_count', '>=', 5)
            ->with('caseType')
            ->orderByDesc('adjournment_count')
            ->limit(20)
            ->get();

        return view('analytics.adjournments', compact(
            'byReason', 'byRequester', 'highAdjournmentCases'
        ));
    }

    public function atRiskCases(DelayPredictor $predictor)
    {
        $atRiskCases = $predictor->findAtRiskCases();
        return view('analytics.at-risk', compact('atRiskCases'));
    }
}
