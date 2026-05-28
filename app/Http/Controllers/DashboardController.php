<?php

namespace App\Http\Controllers;

use App\Models\CauseList;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Services\DelayPredictor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DelayPredictor $predictor)
    {
        $user = $request->user();

        // Pick a dashboard variant based on role
        if ($user->isJudge()) {
            return $this->judgeDashboard($user, $predictor);
        }
        if ($user->isLawyer()) {
            return $this->lawyerDashboard($user);
        }
        if ($user->isLitigant()) {
            return $this->litigantDashboard($user);
        }
        // Admin and staff see the operational overview
        return $this->adminDashboard($predictor);
    }

    private function judgeDashboard($user, DelayPredictor $predictor)
    {
        $judge = $user->judgeProfile;
        if (! $judge) {
            return view('dashboard.empty', ['message' => 'No judge profile linked to this account.']);
        }

        $todayList = CauseList::where('judge_id', $judge->id)
            ->whereDate('list_date', Carbon::today())
            ->with('items.case.caseType')
            ->first();

        $pendingHearings = Hearing::where('judge_id', $judge->id)
            ->where('status', Hearing::STATUS_SCHEDULED)
            ->whereDate('scheduled_date', '>=', Carbon::today())
            ->count();

        $stats = [
            'total_assigned' => $judge->assignedCases()->active()->count(),
            'fast_track' => $judge->assignedCases()->active()->fastTrack()->count(),
            'overdue' => $judge->assignedCases()->active()->overdue()->count(),
            'pending_hearings' => $pendingHearings,
        ];

        $atRisk = $predictor->findAtRiskCases()
            ->filter(fn ($a) => $a['case']->assigned_judge_id === $judge->id)
            ->take(10);

        return view('dashboard.judge', compact('judge', 'todayList', 'stats', 'atRisk'));
    }

    private function lawyerDashboard($user)
    {
        $cases = CourtCase::whereHas('lawyers', fn ($q) =>
                $q->where('lawyer_id', $user->id)->where('is_active', true))
            ->with(['caseType', 'court', 'assignedJudge.user'])
            ->orderByDesc('priority_score')
            ->get();

        $upcomingHearings = Hearing::whereHas('case.lawyers', fn ($q) =>
                $q->where('lawyer_id', $user->id)->where('is_active', true))
            ->whereDate('scheduled_date', '>=', Carbon::today())
            ->where('status', Hearing::STATUS_SCHEDULED)
            ->with('case.caseType', 'court')
            ->orderBy('scheduled_date')
            ->take(10)
            ->get();

        return view('dashboard.lawyer', compact('cases', 'upcomingHearings'));
    }

    private function litigantDashboard($user)
    {
        $cases = CourtCase::whereHas('parties', fn ($q) => $q->where('user_id', $user->id))
            ->with(['caseType', 'court', 'assignedJudge.user'])
            ->get();

        return view('dashboard.litigant', compact('cases'));
    }

    private function adminDashboard(DelayPredictor $predictor)
    {
        $stats = [
            'total_active' => CourtCase::active()->count(),
            'total_disposed_this_month' => CourtCase::where('status', CourtCase::STATUS_DISPOSED)
                ->whereMonth('disposed_on', Carbon::now()->month)
                ->count(),
            'fast_track' => CourtCase::active()->fastTrack()->count(),
            'overdue' => CourtCase::active()->overdue()->count(),
            'hearings_today' => Hearing::whereDate('scheduled_date', Carbon::today())->count(),
        ];

        $trackBreakdown = CourtCase::active()
            ->selectRaw('track, count(*) as count')
            ->groupBy('track')
            ->pluck('count', 'track');

        $atRiskCount = $predictor->findAtRiskCases()->count();

        $recentCases = CourtCase::with('caseType', 'court')
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.admin', compact('stats', 'trackBreakdown', 'atRiskCount', 'recentCases'));
    }
}
