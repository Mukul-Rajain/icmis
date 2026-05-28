<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\CourtCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Live counters: cases filed vs resolved in the past 30 days.
     */
    public function stats(): JsonResponse
    {
        $since = Carbon::now()->subDays(30);

        $filed    = CourtCase::where('filing_date', '>=', $since)->count();
        $resolved = CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                             ->where('disposed_on', '>=', $since)
                             ->count();
        $backlog  = CourtCase::whereNotIn('status', [
            CourtCase::STATUS_DISPOSED,
            CourtCase::STATUS_DRAFT,
        ])->count();

        return response()->json([
            'period_days' => 30,
            'filed'       => $filed,
            'resolved'    => $resolved,
            'backlog'     => $backlog,
            'resolution_rate' => $filed > 0
                ? round(($resolved / $filed) * 100, 1)
                : 0,
        ]);
    }

    /**
     * Performance leaderboard — judges ranked by disposal rate (30-day window).
     */
    public function leaderboard(): JsonResponse
    {
        $since = Carbon::now()->subDays(30);

        // Aggregate disposals per judge
        $judges = User::where('role', 'judge')->get();

        $rankings = $judges->map(function (User $judge) use ($since) {
            $judgeId  = (string) $judge->_id;
            $total    = CourtCase::where('assigned_judge_id', $judgeId)
                                 ->where('status', '!=', CourtCase::STATUS_DRAFT)
                                 ->count();
            $disposed = CourtCase::where('assigned_judge_id', $judgeId)
                                 ->where('status', CourtCase::STATUS_DISPOSED)
                                 ->where('disposed_on', '>=', $since)
                                 ->count();

            return [
                'judge_name'    => $judge->name,
                'designation'   => $judge->judge_profile['designation'] ?? null,
                'court_id'      => $judge->judge_profile['court_id']    ?? null,
                'cases_total'   => $total,
                'cases_disposed'=> $disposed,
                'disposal_rate' => $total > 0 ? round(($disposed / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('disposal_rate')->values();

        return response()->json(['leaderboard' => $rankings]);
    }

    public function courts(): JsonResponse
    {
        $courts = Court::where('is_active', true)
            ->get(['_id', 'name', 'code', 'city', 'state'])
            ->map(fn($c) => [
                'id'   => (string) $c->_id,
                'name' => $c->name,
                'code' => $c->code,
                'city' => $c->city,
            ]);

        return response()->json(['courts' => $courts]);
    }

    /**
     * Public case-status lookup by case number (no auth).
     */
    public function caseStatus(Request $request): JsonResponse
    {
        $request->validate([
            'case_number' => 'required|string',
        ]);

        $case = CourtCase::where('case_number', $request->case_number)->first();

        if (! $case) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return response()->json([
            'case_number'       => $case->case_number,
            'title'             => $case->title,
            'status'            => $case->status,
            'case_type'         => $case->case_type,
            'filing_date'       => $case->filing_date?->toDateString(),
            'next_hearing_date' => $case->next_hearing_date?->toDateString(),
            'hearing_count'     => $case->hearing_count,
        ]);
    }
}
