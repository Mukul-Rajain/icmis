<?php

namespace App\Http\Controllers\Api\Filer;

use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    /**
     * List all cases filed by the authenticated filer.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CourtCase::forFiler((string) $request->user()->_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cases = $query->orderBy('filing_date', 'desc')
                       ->paginate(20);

        $cases->through(fn($c) => $this->format($c));

        return response()->json($cases);
    }

    /**
     * Create a new draft case.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'                      => 'required|string|max:500',
            'description'                => 'required|string',
            'court_id'                   => 'required|string',
            'case_type'                  => 'required|in:civil,criminal,family,commercial,writ',
            'parties'                    => 'required|array|min:2',
            'parties.*.party_type'       => 'required|in:petitioner,respondent,intervenor',
            'parties.*.name'             => 'required|string',
            'parties.*.address'          => 'required|string',
            'parties.*.is_senior_citizen'=> 'boolean',
            'parties.*.is_in_custody'    => 'boolean',
        ]);

        $userId = (string) $request->user()->_id;

        $case = CourtCase::create([
            'title'                      => $data['title'],
            'description'                => $data['description'],
            'court_id'                   => $data['court_id'],
            'case_type'                  => $data['case_type'],
            'parties'                    => $data['parties'],
            'filed_by'                   => $userId,
            'filing_date'                => now(),
            'status'                     => CourtCase::STATUS_DRAFT,
            'lawyers'                    => [],
            'documents'                  => [],
            'hearings'                   => [],
            'mentioning_requests'        => [],
            'scrutiny'                   => ['defect_flags' => [], 'reviewed_by' => null],
            'hearing_count'              => 0,
            'adjournment_count'          => 0,
            'is_high_priority'           => false,
            'has_interim_relief_pending' => false,
        ]);

        return response()->json(['case' => $this->format($case)], 201);
    }

    /**
     * Show a single case (owned by the filer).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $case = $this->ownedCase($request, $id);
        return response()->json(['case' => $this->format($case)]);
    }

    /**
     * Update a draft case before submission.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $case = $this->ownedCase($request, $id);

        if ($case->status !== CourtCase::STATUS_DRAFT) {
            return response()->json(['message' => 'Only draft cases can be edited.'], 422);
        }

        $data = $request->validate([
            'title'       => 'sometimes|string|max:500',
            'description' => 'sometimes|string',
            'parties'     => 'sometimes|array',
        ]);

        $case->update($data);
        return response()->json(['case' => $this->format($case->fresh())]);
    }

    /**
     * Submit a draft for registry scrutiny.
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $case = $this->ownedCase($request, $id);

        if ($case->status !== CourtCase::STATUS_DRAFT) {
            return response()->json(['message' => 'Case is already submitted.'], 422);
        }

        if (empty($case->documents)) {
            return response()->json(['message' => 'At least one document must be attached before submission.'], 422);
        }

        $case->update(['status' => CourtCase::STATUS_SUBMITTED]);

        return response()->json([
            'message' => 'Case submitted for scrutiny.',
            'case'    => $this->format($case->fresh()),
        ]);
    }

    /**
     * Upload a document to an existing case.
     */
    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        $case = $this->ownedCase($request, $id);

        $request->validate([
            'label' => 'required|string|max:100',
            'file'  => 'required|file|mimes:pdf,doc,docx,jpg,png|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store("cases/{$id}/documents", 'local');

        $doc = [
            'doc_id'      => (string) Str::uuid(),
            'label'       => $request->label,
            'file_path'   => $path,
            'mime_type'   => $file->getMimeType(),
            'uploaded_by' => (string) $request->user()->_id,
            'uploaded_at' => now()->toIso8601String(),
            'is_verified' => false,
        ];

        $documents   = $case->documents ?? [];
        $documents[] = $doc;
        $case->update(['documents' => $documents]);

        return response()->json(['document' => $doc], 201);
    }

    /**
     * Remove an attached document (draft cases only).
     */
    public function deleteDocument(Request $request, string $id, string $docId): JsonResponse
    {
        $case = $this->ownedCase($request, $id);

        if (! in_array($case->status, [CourtCase::STATUS_DRAFT, CourtCase::STATUS_DEFECTIVE])) {
            return response()->json(['message' => 'Documents cannot be removed at this stage.'], 422);
        }

        $documents = array_values(array_filter(
            $case->documents ?? [],
            fn($d) => ($d['doc_id'] ?? '') !== $docId
        ));

        $case->update(['documents' => $documents]);
        return response()->json(['message' => 'Document removed.']);
    }

    /**
     * Stream a document file so the browser can render it inline (PDF viewer).
     */
    public function serveDocument(Request $request, string $id, string $docId)
    {
        $case = $this->ownedCase($request, $id);

        $doc = collect($case->documents ?? [])
            ->firstWhere('doc_id', $docId);

        if (! $doc) {
            abort(404, 'Document not found.');
        }

        $path = $doc['file_path'];

        // Seeded / placeholder documents
        if (str_starts_with($path, 'seeded/')) {
            $full = storage_path('app/' . $path);
        } else {
            $full = storage_path('app/' . $path);
        }

        if (! file_exists($full)) {
            abort(404, 'File missing from storage.');
        }

        $mime = $doc['mime_type'] ?? 'application/octet-stream';

        return response()->file($full, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Request an out-of-turn / emergency hearing (mentioning request).
     */
    public function requestMention(Request $request, string $id): JsonResponse
    {
        $case = $this->ownedCase($request, $id);

        if (! in_array($case->status, [
            CourtCase::STATUS_ACCEPTED,
            CourtCase::STATUS_SCHEDULED,
            CourtCase::STATUS_ADJOURNED,
        ])) {
            return response()->json(['message' => 'Mentioning requests are only allowed for scheduled cases.'], 422);
        }

        $data = $request->validate([
            'reason'  => 'required|string|max:1000',
            'urgency' => 'required|in:urgent,very_urgent',
        ]);

        $mention = [
            'request_id'   => (string) Str::uuid(),
            'requested_by' => (string) $request->user()->_id,
            'reason'       => $data['reason'],
            'urgency'      => $data['urgency'],
            'status'       => 'pending',
            'judge_note'   => null,
            'created_at'   => now()->toIso8601String(),
            'resolved_at'  => null,
        ];

        $mentions   = $case->mentioning_requests ?? [];
        $mentions[] = $mention;
        $case->update(['mentioning_requests' => $mentions]);

        return response()->json(['message' => 'Mentioning request submitted.', 'request' => $mention], 201);
    }

    /**
     * Defect inbox: cases rejected by registry + cases just accepted with dates.
     */
    public function inbox(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->_id;

        $defective = CourtCase::forFiler($userId)
            ->where('status', CourtCase::STATUS_DEFECTIVE)
            ->get()
            ->map(fn($c) => $this->format($c));

        $accepted = CourtCase::forFiler($userId)
            ->whereIn('status', [CourtCase::STATUS_ACCEPTED, CourtCase::STATUS_SCHEDULED])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($c) => $this->format($c));

        return response()->json([
            'defective' => $defective,
            'accepted'  => $accepted,
        ]);
    }

    /**
     * Calendar schedule: all upcoming hearings for this filer's cases.
     */
    public function schedule(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->_id;

        $cases = CourtCase::forFiler($userId)
            ->whereNotNull('next_hearing_date')
            ->where('next_hearing_date', '>=', Carbon::today())
            ->orderBy('next_hearing_date', 'asc')
            ->get()
            ->map(fn($c) => [
                'id'                => (string) $c->_id,
                'case_number'       => $c->case_number,
                'title'             => $c->title,
                'status'            => $c->status,
                'next_hearing_date' => $c->next_hearing_date?->toIso8601String(),
                'court_id'          => $c->court_id,
                'is_high_priority'  => $c->is_high_priority,
            ]);

        return response()->json(['schedule' => $cases]);
    }

    // ─── Private helpers ──────────────────────────────────────

    private function ownedCase(Request $request, string $id): CourtCase
    {
        $case = CourtCase::findOrFail($id);
        $userId = (string) $request->user()->_id;

        // Allow access if filer or one of the representing lawyers
        $lawyerIds = array_column($case->lawyers ?? [], 'user_id');
        if ((string) $case->filed_by !== $userId && ! in_array($userId, $lawyerIds, true)) {
            abort(403, 'You do not have access to this case.');
        }

        return $case;
    }

    private function format(CourtCase $case): array
    {
        return [
            'id'                   => (string) $case->_id,
            'case_number'          => $case->case_number,
            'title'                => $case->title,
            'description'          => $case->description,
            'status'               => $case->status,
            'case_type'            => $case->case_type,
            'track'                => $case->track,
            'priority_score'       => $case->priority_score,
            'is_high_priority'     => $case->is_high_priority,
            'court_id'             => $case->court_id,
            'filing_date'          => $case->filing_date?->toDateString(),
            'next_hearing_date'    => $case->next_hearing_date?->toIso8601String(),
            'hearing_count'        => $case->hearing_count,
            'adjournment_count'    => $case->adjournment_count,
            'parties'              => $case->parties,
            'documents'            => $case->documents,
            'scrutiny'             => $case->scrutiny,
            'mentioning_requests'  => $case->mentioning_requests,
        ];
    }
}
