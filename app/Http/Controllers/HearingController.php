<?php

namespace App\Http\Controllers;

use App\Models\Adjournment;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Services\PriorityScorer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HearingController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        $hearings = Hearing::with(['case.caseType', 'judge.user', 'court'])
            ->whereDate('scheduled_date', $date)
            ->orderBy('scheduled_time')
            ->paginate(30);

        return view('hearings.index', compact('hearings', 'date'));
    }

    public function outcomeForm(Hearing $hearing)
    {
        $hearing->load('case.caseType', 'judge.user');
        return view('hearings.outcome', compact('hearing'));
    }

    public function recordOutcome(Request $request, Hearing $hearing, PriorityScorer $scorer)
    {
        $validated = $request->validate([
            'outcome' => 'required|string',
            'next_action' => 'nullable|string',
            'next_hearing_date' => 'nullable|date|after:today',
            'new_stage' => 'nullable|in:registered,notice_issued,reply_filed,evidence,arguments,judgment_reserved,disposed',
        ]);

        $hearing->update([
            'status' => Hearing::STATUS_COMPLETED,
            'outcome' => $validated['outcome'],
            'next_action' => $validated['next_action'] ?? null,
            'next_hearing_date' => $validated['next_hearing_date'] ?? null,
            'actual_end_time' => now(),
        ]);

        $case = $hearing->case;
        $case->hearing_count = $case->hearing_count + 1;
        $case->last_hearing_date = $hearing->scheduled_date;
        $case->next_hearing_date = $validated['next_hearing_date'] ?? null;

        if (! empty($validated['new_stage'])) {
            $case->current_stage = $validated['new_stage'];
            if ($validated['new_stage'] === 'disposed') {
                $case->status = CourtCase::STATUS_DISPOSED;
                $case->disposed_on = now();
            }
        }
        $case->save();

        // Rescore because stage/age may have changed
        $scorer->scoreAndPersist($case->fresh('caseType'));

        return back()->with('success', 'Hearing outcome recorded.');
    }

    public function adjourn(Request $request, Hearing $hearing, PriorityScorer $scorer)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'reason_category' => 'required|in:lawyer_unavailable,witness_unavailable,document_pending,judge_unavailable,medical,natural_calamity,other',
            'new_date' => 'required|date|after:today',
            'judge_remarks' => 'nullable|string',
        ]);

        Adjournment::create([
            'hearing_id' => $hearing->id,
            'case_id' => $hearing->case_id,
            'requested_by_user_id' => auth()->id(),
            'requested_by_role' => $this->detectRequesterRole($request->user()),
            'reason' => $validated['reason'],
            'reason_category' => $validated['reason_category'],
            'granted' => true,
            'new_date' => $validated['new_date'],
            'judge_remarks' => $validated['judge_remarks'] ?? null,
            'decided_by_judge_id' => $hearing->judge_id,
        ]);

        $hearing->update(['status' => Hearing::STATUS_ADJOURNED]);

        // Increment case adjournment count and re-score
        $case = $hearing->case;
        $case->increment('adjournment_count');
        $case->next_hearing_date = $validated['new_date'];
        $case->save();
        $scorer->scoreAndPersist($case->fresh('caseType'));

        // Create the new scheduled hearing
        Hearing::create([
            'case_id' => $case->id,
            'judge_id' => $hearing->judge_id,
            'court_id' => $hearing->court_id,
            'scheduled_date' => $validated['new_date'],
            'status' => Hearing::STATUS_SCHEDULED,
            'stage_at_hearing' => $hearing->stage_at_hearing,
        ]);

        $message = 'Hearing adjourned to ' . Carbon::parse($validated['new_date'])->format('d M Y') . '.';

        // Flag excessive adjournments
        if ($case->adjournment_count >= 5) {
            $message .= " ⚠ This case now has {$case->adjournment_count} adjournments — flagged for review.";
        }

        return back()->with('success', $message);
    }

    private function detectRequesterRole($user): string
    {
        if ($user->isJudge()) return 'judge';
        if ($user->isCourtStaff()) return 'court_staff';
        // For lawyers, default to petitioner side (real system would let them pick)
        return 'lawyer_petitioner';
    }
}
