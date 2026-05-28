<?php

namespace App\Http\Controllers\Api\Registry;

use App\Events\CaseDefective;
use App\Events\CaseScheduled;
use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistryController extends Controller
{
    /**
     * Paginated feed of cases awaiting scrutiny, filtered to this registry's court.
     */
    public function queue(Request $request): JsonResponse
    {
        $courtId = $request->user()->registry_profile['court_id'] ?? null;

        $query = CourtCase::whereIn('status', [
            CourtCase::STATUS_SUBMITTED,
            CourtCase::STATUS_UNDER_SCRUTINY,
        ]);

        if ($courtId) {
            $query->where('court_id', $courtId);
        }

        $cases = $query->orderBy('filing_date', 'asc')->paginate(25);
        $cases->through(fn($c) => $this->format($c));

        return response()->json($cases);
    }

    /**
     * Full case detail for scrutiny review.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $case = CourtCase::findOrFail($id);
        $this->authoriseCourt($request, $case);

        if ($case->status === CourtCase::STATUS_SUBMITTED) {
            $case->update(['status' => CourtCase::STATUS_UNDER_SCRUTINY]);
        }

        return response()->json(['case' => $this->format($case->fresh())]);
    }

    /**
     * List judges available for this registry's court.
     */
    public function judges(Request $request): JsonResponse
    {
        $courtId = $request->user()->registry_profile['court_id'] ?? null;

        $judges = User::where('role', User::ROLE_JUDGE)
            ->where('is_active', true)
            ->get()
            ->filter(function ($j) use ($courtId) {
                if (! $courtId) return true;
                return ($j->judge_profile['court_id'] ?? null) === $courtId;
            })
            ->map(fn($j) => [
                'id'          => (string) $j->_id,
                'name'        => $j->name,
                'designation' => $j->judge_profile['designation'] ?? null,
                'bench'       => $j->judge_profile['bench_number'] ?? null,
            ])
            ->values();

        return response()->json(['judges' => $judges]);
    }

    /**
     * Serve a document file so registry staff can read it inline.
     */
    public function serveDocument(Request $request, string $id, string $docId)
    {
        $case = CourtCase::findOrFail($id);
        $this->authoriseCourt($request, $case);

        $doc = collect($case->documents ?? [])->firstWhere('doc_id', $docId);
        if (! $doc) abort(404, 'Document not found.');

        $full = storage_path('app/' . $doc['file_path']);
        if (! file_exists($full)) abort(404, 'File missing from storage.');

        return response()->file($full, [
            'Content-Type'        => $doc['mime_type'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($doc['file_path']) . '"',
        ]);
    }

    /**
     * Approve a case: tag it, compute priority score, and auto-schedule.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $case = CourtCase::findOrFail($id);
        $this->authoriseCourt($request, $case);

        if (! in_array($case->status, [CourtCase::STATUS_SUBMITTED, CourtCase::STATUS_UNDER_SCRUTINY])) {
            return response()->json(['message' => 'Case is not pending scrutiny.'], 422);
        }

        $data = $request->validate([
            'case_type'               => 'required|in:civil,criminal,family,commercial,writ',
            'track'                   => 'required|in:regular,fast,urgent',
            'assigned_judge_id'       => 'required|string',
            'priority_score'          => 'nullable|numeric|min:0|max:100',
            'expected_disposal_date'  => 'nullable|date|after:today',
        ]);

        $registryUserId = (string) $request->user()->_id;
        $score          = $data['priority_score'] ?? $this->computePriorityScore($case, $data['track']);
        $hearingDate    = $this->scheduleNextHearing($data['assigned_judge_id'], $data['track']);
        $caseNumber     = $case->case_number ?? CourtCase::generateCaseNumber(
            $case->court_id,
            $data['case_type']
        );

        $case->update([
            'case_number'           => $caseNumber,
            'status'                => CourtCase::STATUS_SCHEDULED,
            'case_type'             => $data['case_type'],
            'track'                 => $data['track'],
            'assigned_judge_id'     => $data['assigned_judge_id'],
            'priority_score'        => $score,
            'next_hearing_date'     => $hearingDate,
            'expected_disposal_date'=> $data['expected_disposal_date'] ?? null,
            'scrutiny'              => array_merge($case->scrutiny ?? [], [
                'reviewed_by' => $registryUserId,
                'approved_at' => now()->toIso8601String(),
            ]),
        ]);

        broadcast(new CaseScheduled(
            filerId:         (string) $case->filed_by,
            caseId:          (string) $case->_id,
            caseNumber:      $caseNumber,
            nextHearingDate: $hearingDate->toDateString(),
        ));

        return response()->json([
            'message'          => 'Case approved and scheduled.',
            'case_number'      => $caseNumber,
            'next_hearing_date'=> $hearingDate->toDateString(),
        ]);
    }

    /**
     * Reject a case with required defect flags.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $case = CourtCase::findOrFail($id);
        $this->authoriseCourt($request, $case);

        if (! in_array($case->status, [CourtCase::STATUS_SUBMITTED, CourtCase::STATUS_UNDER_SCRUTINY])) {
            return response()->json(['message' => 'Case is not pending scrutiny.'], 422);
        }

        $data = $request->validate([
            'defect_flags'                => 'required|array|min:1',
            'defect_flags.*.code'         => 'required|string',
            'defect_flags.*.label'        => 'required|string',
            'rejection_note'              => 'nullable|string|max:1000',
        ]);

        $registryUserId = (string) $request->user()->_id;

        $case->update([
            'status'  => CourtCase::STATUS_DEFECTIVE,
            'scrutiny'=> [
                'reviewed_by'     => $registryUserId,
                'reviewed_at'     => now()->toIso8601String(),
                'approved_at'     => null,
                'defect_flags'    => array_map(
                    fn($f) => [...$f, 'resolved' => false],
                    $data['defect_flags']
                ),
                'rejection_note'  => $data['rejection_note'] ?? null,
            ],
        ]);

        broadcast(new CaseDefective(
            filerId:     (string) $case->filed_by,
            caseId:      (string) $case->_id,
            caseNumber:  $case->case_number ?? 'N/A',
            defectFlags: $data['defect_flags'],
        ));

        return response()->json(['message' => 'Case marked as defective. Filer has been notified.']);
    }

    // ─── Private helpers ──────────────────────────────────────

    private function format(CourtCase $c): array
    {
        return [
            'id'           => (string) $c->_id,
            'case_number'  => $c->case_number,
            'title'        => $c->title,
            'description'  => $c->description,
            'status'       => $c->status,
            'case_type'    => $c->case_type,
            'track'        => $c->track,
            'court_id'     => $c->court_id,
            'filing_date'  => $c->filing_date?->toDateString(),
            'parties'      => $c->parties,
            'documents'    => $c->documents,
            'scrutiny'     => $c->scrutiny,
            'is_high_priority'           => $c->is_high_priority,
            'has_interim_relief_pending' => $c->has_interim_relief_pending,
        ];
    }

    private function authoriseCourt(Request $request, CourtCase $case): void
    {
        $courtId = $request->user()->registry_profile['court_id'] ?? null;
        if ($courtId && (string) $case->court_id !== $courtId) {
            abort(403, 'This case does not belong to your court.');
        }
    }

    /**
     * Simple priority score: base score adjusted by track and case flags.
     */
    private function computePriorityScore(CourtCase $case, string $track): float
    {
        $score = match ($track) {
            'urgent'  => 80.0,
            'fast'    => 60.0,
            default   => 40.0,
        };

        // Boost for flags
        if ($case->has_interim_relief_pending) $score += 10;

        $seniorCitizenParty = collect($case->parties ?? [])
            ->first(fn($p) => ($p['is_senior_citizen'] ?? false));
        if ($seniorCitizenParty) $score += 5;

        $inCustodyParty = collect($case->parties ?? [])
            ->first(fn($p) => ($p['is_in_custody'] ?? false));
        if ($inCustodyParty) $score += 15;

        return min(100.0, $score);
    }

    /**
     * Find the earliest available weekday slot for the given judge.
     * Urgent: within 7 days. Fast: within 30. Regular: within 60.
     */
    private function scheduleNextHearing(string $judgeId, string $track): Carbon
    {
        $maxDays = match ($track) {
            'urgent'  => 7,
            'fast'    => 30,
            default   => 60,
        };

        // Collect already-booked hearing dates for this judge
        $bookedDates = CourtCase::where('assigned_judge_id', $judgeId)
            ->whereNotNull('next_hearing_date')
            ->pluck('next_hearing_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $date = Carbon::tomorrow();
        $deadline = Carbon::today()->addDays($maxDays);

        while ($date->lte($deadline)) {
            // Skip weekends (Saturday=6, Sunday=0)
            if (! in_array($date->dayOfWeek, [0, 6], true)) {
                $dateStr = $date->toDateString();
                // Limit to 10 hearings per day per judge
                $count = array_count_values($bookedDates)[$dateStr] ?? 0;
                if ($count < 10) {
                    return $date->copy()->setTime(10, 0);
                }
            }
            $date->addDay();
        }

        // Fallback: use the deadline date if no slot found within window
        return $deadline->setTime(10, 0);
    }
}
