<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use App\Models\CourtCase;
use App\Services\DelayPredictor;
use App\Services\PriorityScorer;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $query = CourtCase::with(['caseType', 'court', 'assignedJudge.user'])
            ->orderByDesc('priority_score');

        // Filters
        if ($request->filled('track')) {
            $query->where('track', $request->track);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('case_type_id')) {
            $query->where('case_type_id', $request->case_type_id);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('case_number', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$term}%");
            });
        }

        // Restrict to user's own cases when not admin/judge/staff
        $user = $request->user();
        if ($user->isLawyer()) {
            $query->whereHas('lawyers', fn ($q) =>
                $q->where('lawyer_id', $user->id)->where('is_active', true));
        } elseif ($user->isLitigant()) {
            $query->whereHas('parties', fn ($q) => $q->where('user_id', $user->id));
        }

        $cases = $query->paginate(20)->withQueryString();
        $caseTypes = CaseType::orderBy('name')->get();

        return view('cases.index', compact('cases', 'caseTypes'));
    }

    public function create()
    {
        return view('cases.register');
    }

    public function show(CourtCase $case, DelayPredictor $predictor)
    {
        $case->load([
            'caseType', 'court', 'assignedJudge.user',
            'parties', 'lawyers.lawyer',
            'hearings' => fn ($q) => $q->latest('scheduled_date'),
            'documents', 'priorityScoreHistory' => fn ($q) => $q->latest('computed_at')->limit(10),
        ]);

        $riskAssessment = $predictor->assess($case);
        $latestScoreFactors = $case->priorityScoreHistory->first()?->factors;

        return view('cases.show', compact('case', 'riskAssessment', 'latestScoreFactors'));
    }

    public function rescore(CourtCase $case, PriorityScorer $scorer)
    {
        $newScore = $scorer->scoreAndPersist($case->fresh('caseType'), 'user:' . auth()->id());
        return back()->with('success', "Priority score updated to {$newScore}");
    }

    public function updateStage(Request $request, CourtCase $case, PriorityScorer $scorer)
    {
        $validated = $request->validate([
            'current_stage' => 'required|in:registered,notice_issued,reply_filed,evidence,arguments,judgment_reserved,disposed,transferred,withdrawn',
            'disposal_remarks' => 'nullable|string',
        ]);

        $case->update($validated);

        if ($validated['current_stage'] === 'disposed') {
            $case->update([
                'status' => CourtCase::STATUS_DISPOSED,
                'disposed_on' => now(),
            ]);
        } else {
            // Stage changes affect priority — rescore
            $scorer->scoreAndPersist($case->fresh('caseType'), 'user:' . auth()->id());
        }

        return back()->with('success', 'Case stage updated');
    }

    public function timeline(CourtCase $case)
    {
        $case->load([
            'hearings.adjournments',
            'priorityScoreHistory' => fn ($q) => $q->orderBy('computed_at'),
        ]);

        return view('cases.timeline', compact('case'));
    }
}
