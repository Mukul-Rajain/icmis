<?php

namespace App\Http\Controllers\Api\Judge;

use App\Events\CauseListUpdated;
use App\Events\MentionAccepted;
use App\Events\MentionRejected;
use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JudgeController extends Controller
{
    const DAILY_CASE_LIMIT = 20;

    /**
     * Today's cause list for the authenticated judge, ordered by cause_list_position.
     * Hard cap: DAILY_CASE_LIMIT cases per day.
     */
    public function causeList(Request $request): JsonResponse
    {
        $judgeId = (string) $request->user()->_id;

        $cases = CourtCase::forJudge($judgeId)
            ->scheduledToday()
            ->orderBy('is_high_priority', 'desc')
            ->orderBy('cause_list_position', 'asc')
            ->limit(self::DAILY_CASE_LIMIT)
            ->get()
            ->map(fn($c) => $this->formatForCauseList($c));

        return response()->json([
            'date'        => Carbon::today()->toDateString(),
            'judge_id'    => $judgeId,
            'case_count'  => $cases->count(),
            'daily_limit' => self::DAILY_CASE_LIMIT,
            'cases'       => $cases,
        ]);
    }

    /**
     * Drag-and-drop reorder: accepts an ordered array of case IDs.
     * Updates cause_list_position on each and broadcasts to filer channels.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ordered_ids'   => 'required|array|min:1',
            'ordered_ids.*' => 'required|string',
        ]);

        $judgeId = (string) $request->user()->_id;

        foreach ($request->ordered_ids as $position => $caseId) {
            CourtCase::where('_id', $caseId)
                     ->where('assigned_judge_id', $judgeId)
                     ->update(['cause_list_position' => $position + 1]);
        }

        $judge = $request->user();
        $courtId = $judge->judge_profile['court_id'] ?? null;
        if ($courtId) {
            broadcast(new CauseListUpdated(
                courtId:  $courtId,
                judgeId:  $judgeId,
                date:     Carbon::today()->toDateString(),
            ));
        }

        return response()->json(['message' => 'Cause list reordered.']);
    }

    /**
     * Toggle high-priority flag on a case. Change syncs to filer dashboard via WS.
     */
    public function togglePriority(Request $request, string $id): JsonResponse
    {
        $case    = $this->judgesCase($request, $id);
        $newFlag = ! $case->is_high_priority;
        $case->update(['is_high_priority' => $newFlag]);

        $courtId = $request->user()->judge_profile['court_id'] ?? null;
        if ($courtId) {
            broadcast(new CauseListUpdated(
                courtId: $courtId,
                judgeId: (string) $request->user()->_id,
                date:    Carbon::today()->toDateString(),
            ));
        }

        return response()->json([
            'id'              => (string) $case->_id,
            'is_high_priority'=> $newFlag,
        ]);
    }

    /**
     * Read-only case preview: full document including embedded docs.
     */
    public function preview(Request $request, string $id): JsonResponse
    {
        $case = $this->judgesCase($request, $id);

        return response()->json([
            'id'                   => (string) $case->_id,
            'case_number'          => $case->case_number,
            'title'                => $case->title,
            'description'          => $case->description,
            'case_type'            => $case->case_type,
            'track'                => $case->track,
            'status'               => $case->status,
            'priority_score'       => $case->priority_score,
            'is_high_priority'     => $case->is_high_priority,
            'filing_date'          => $case->filing_date?->toDateString(),
            'parties'              => $case->parties,
            'lawyers'              => $case->lawyers,
            'documents'            => $case->documents,
            'hearings'             => $case->hearings,
            'hearing_count'        => $case->hearing_count,
            'adjournment_count'    => $case->adjournment_count,
            'age_in_days'          => $case->age_in_days,
            'is_overdue'           => $case->is_overdue,
        ]);
    }

    /**
     * Stream a case document so the judge can read it inline.
     */
    public function serveDocument(Request $request, string $id, string $docId)
    {
        $case = $this->judgesCase($request, $id);

        $doc = collect($case->documents ?? [])->firstWhere('doc_id', $docId);
        if (! $doc) abort(404, 'Document not found.');

        $full = storage_path('app/' . $doc['file_path']);
        if (! file_exists($full)) abort(404, 'File missing.');

        return response()->file($full, [
            'Content-Type'        => $doc['mime_type'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($doc['file_path']) . '"',
        ]);
    }

    /**
     * Record a hearing outcome: heard / adjourned / part-heard.
     */
    public function recordHearing(Request $request, string $id): JsonResponse
    {
        $case = $this->judgesCase($request, $id);

        $data = $request->validate([
            'outcome'             => 'required|in:heard,adjourned,part_heard,disposed',
            'order_text'          => 'nullable|string',
            'adjourned_to'        => 'required_if:outcome,adjourned|nullable|date|after:today',
            'adjournment_reason'  => 'required_if:outcome,adjourned|nullable|string',
            'disposal_remarks'    => 'required_if:outcome,disposed|nullable|string',
        ]);

        $judgeId = (string) $request->user()->_id;

        $hearingEntry = [
            'hearing_id'         => (string) Str::uuid(),
            'scheduled_at'       => $case->next_hearing_date?->toIso8601String(),
            'purpose'            => $case->current_purpose ?? 'Hearing',
            'outcome'            => $data['outcome'],
            'order_text'         => $data['order_text'] ?? null,
            'adjourned_to'       => $data['adjourned_to'] ?? null,
            'adjournment_reason' => $data['adjournment_reason'] ?? null,
            'recorded_by'        => $judgeId,
            'recorded_at'        => now()->toIso8601String(),
        ];

        $hearings   = $case->hearings ?? [];
        $hearings[] = $hearingEntry;

        $updates = [
            'hearings'          => $hearings,
            'last_hearing_date' => $case->next_hearing_date,
            'hearing_count'     => ($case->hearing_count ?? 0) + 1,
        ];

        if ($data['outcome'] === 'adjourned') {
            $updates['status']             = CourtCase::STATUS_ADJOURNED;
            $updates['next_hearing_date']  = Carbon::parse($data['adjourned_to']);
            $updates['adjournment_count']  = ($case->adjournment_count ?? 0) + 1;
        } elseif ($data['outcome'] === 'disposed') {
            $updates['status']           = CourtCase::STATUS_DISPOSED;
            $updates['disposed_on']      = now();
            $updates['disposal_remarks'] = $data['disposal_remarks'] ?? null;
            $updates['next_hearing_date']= null;
        } else {
            $updates['status']           = CourtCase::STATUS_HEARD;
            $updates['next_hearing_date']= null;
        }

        $case->update($updates);

        // TODO: Broadcast HearingRecorded to case channel

        return response()->json(['message' => 'Hearing recorded.', 'hearing' => $hearingEntry]);
    }

    /**
     * Pending mentioning requests for this judge's cases.
     */
    public function mentionQueue(Request $request): JsonResponse
    {
        $judgeId = (string) $request->user()->_id;

        // Load all active cases for this judge; filter pending mentions in PHP
        // (mentioning_requests is stored as JSON string, so $elemMatch can't reach inside it)
        $cases = CourtCase::where('assigned_judge_id', $judgeId)
            ->whereNotIn('status', [CourtCase::STATUS_DISPOSED, CourtCase::STATUS_DRAFT])
            ->get();

        $mentions = [];
        foreach ($cases as $case) {
            foreach (($case->mentioning_requests ?? []) as $req) {
                if (($req['status'] ?? '') === 'pending') {
                    $mentions[] = [
                        'case_id'    => (string) $case->_id,
                        'case_number'=> $case->case_number,
                        'case_title' => $case->title,
                        'request'    => $req,
                    ];
                }
            }
        }

        // Sort by urgency (very_urgent first), then chronological
        usort($mentions, function ($a, $b) {
            $urgencyWeight = ['very_urgent' => 2, 'urgent' => 1];
            $wa = $urgencyWeight[$a['request']['urgency'] ?? ''] ?? 0;
            $wb = $urgencyWeight[$b['request']['urgency'] ?? ''] ?? 0;
            if ($wa !== $wb) return $wb <=> $wa;
            return strcmp($a['request']['created_at'], $b['request']['created_at']);
        });

        return response()->json(['mentions' => $mentions]);
    }

    /**
     * Accept a mentioning request — case gets moved to top of today's list.
     */
    public function acceptMention(Request $request, string $caseId, string $reqId): JsonResponse
    {
        $case = $this->judgesCase($request, $caseId);

        $data = $request->validate([
            'judge_note'   => 'nullable|string|max:500',
            'hearing_date' => 'required|date',
        ]);

        $mentions = array_map(function ($req) use ($reqId, $data) {
            if (($req['request_id'] ?? '') === $reqId) {
                return array_merge($req, [
                    'status'      => 'accepted',
                    'judge_note'  => $data['judge_note'] ?? null,
                    'resolved_at' => now()->toIso8601String(),
                ]);
            }
            return $req;
        }, $case->mentioning_requests ?? []);

        $case->update([
            'mentioning_requests' => $mentions,
            'next_hearing_date'   => Carbon::parse($data['hearing_date']),
            'status'              => CourtCase::STATUS_SCHEDULED,
            'cause_list_position' => 1, // Push to top
        ]);

        broadcast(new MentionAccepted(
            filerId:        (string) $case->filed_by,
            caseId:         (string) $case->_id,
            caseNumber:     $case->case_number ?? 'N/A',
            newHearingDate: Carbon::parse($data['hearing_date'])->toDateString(),
            judgeNote:      $data['judge_note'] ?? null,
        ));

        return response()->json(['message' => 'Mentioning request accepted. Filer notified.']);
    }

    /**
     * Reject a mentioning request — normal schedule preserved.
     */
    public function rejectMention(Request $request, string $caseId, string $reqId): JsonResponse
    {
        $case = $this->judgesCase($request, $caseId);

        $data = $request->validate([
            'judge_note' => 'required|string|max:500',
        ]);

        $mentions = array_map(function ($req) use ($reqId, $data) {
            if (($req['request_id'] ?? '') === $reqId) {
                return array_merge($req, [
                    'status'      => 'rejected',
                    'judge_note'  => $data['judge_note'],
                    'resolved_at' => now()->toIso8601String(),
                ]);
            }
            return $req;
        }, $case->mentioning_requests ?? []);

        $case->update(['mentioning_requests' => $mentions]);

        broadcast(new MentionRejected(
            filerId:     (string) $case->filed_by,
            caseId:      (string) $case->_id,
            caseNumber:  $case->case_number ?? 'N/A',
            judgeNote:   $data['judge_note'],
        ));

        return response()->json(['message' => 'Mentioning request rejected. Filer notified.']);
    }

    // ─── Private helpers ──────────────────────────────────────

    private function judgesCase(Request $request, string $id): CourtCase
    {
        $case    = CourtCase::findOrFail($id);
        $judgeId = (string) $request->user()->_id;

        if ((string) $case->assigned_judge_id !== $judgeId) {
            abort(403, 'This case is not assigned to you.');
        }

        return $case;
    }

    private function formatForCauseList(CourtCase $case): array
    {
        return [
            'id'                  => (string) $case->_id,
            'case_number'         => $case->case_number,
            'title'               => $case->title,
            'status'              => $case->status,
            'case_type'           => $case->case_type,
            'track'               => $case->track,
            'cause_list_position' => $case->cause_list_position,
            'is_high_priority'    => $case->is_high_priority,
            'priority_score'      => $case->priority_score,
            'next_hearing_date'   => $case->next_hearing_date?->toIso8601String(),
            'parties'             => array_slice($case->parties ?? [], 0, 2), // petitioner + respondent only
            'age_in_days'         => $case->age_in_days,
            'is_overdue'          => $case->is_overdue,
            'pending_mention'     => collect($case->mentioning_requests ?? [])
                ->contains(fn($r) => ($r['status'] ?? '') === 'pending'),
        ];
    }
}
