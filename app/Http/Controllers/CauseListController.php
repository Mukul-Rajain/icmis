<?php

namespace App\Http\Controllers;

use App\Models\CauseList;
use App\Models\Judge;
use App\Services\CauseListGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CauseListController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $lists = CauseList::whereDate('list_date', $date)
            ->with(['judge.user', 'court', 'items'])
            ->get();

        return view('cause-lists.index', compact('lists', 'date'));
    }

    public function generateForm()
    {
        $judges = Judge::with('user', 'court')->where('is_available', true)->get();
        return view('cause-lists.generate', compact('judges'));
    }

    public function generate(Request $request, CauseListGenerator $generator)
    {
        $validated = $request->validate([
            'judge_id' => 'required|exists:judges,id',
            'list_date' => 'required|date|after_or_equal:today',
        ]);

        $judge = Judge::findOrFail($validated['judge_id']);

        try {
            $result = $generator->generate(
                $judge,
                Carbon::parse($validated['list_date']),
                $request->user()->id
            );

            $message = "Generated cause list with {$result['cause_list']->total_cases} cases.";

            if (! empty($result['conflicts'])) {
                $message .= ' ' . count($result['conflicts']) . ' lawyer conflict(s) detected — please review.';
            }
            if (! empty($result['skipped'])) {
                $message .= ' ' . count($result['skipped']) . ' cases skipped (over capacity).';
            }

            return redirect()
                ->route('cause-lists.show', $result['cause_list'])
                ->with('success', $message)
                ->with('conflicts', $result['conflicts'])
                ->with('skipped', $result['skipped']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(CauseList $causeList)
    {
        $causeList->load([
            'judge.user',
            'court',
            'items.case.caseType',
            'items.case.parties',
            'items.case.lawyers.lawyer',
        ]);

        return view('cause-lists.show', compact('causeList'));
    }

    public function publish(CauseList $causeList)
    {
        $causeList->update([
            'status' => CauseList::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // TODO: Dispatch notifications to all stakeholders (lawyers, litigants)
        // \App\Jobs\NotifyCauseListPublished::dispatch($causeList);

        return back()->with('success', 'Cause list published. Notifications dispatched.');
    }

    public function downloadPdf(CauseList $causeList)
    {
        $causeList->load([
            'judge.user', 'court',
            'items.case.caseType', 'items.case.parties', 'items.case.lawyers.lawyer',
        ]);

        $pdf = Pdf::loadView('cause-lists.pdf', compact('causeList'));
        return $pdf->download("CauseList-{$causeList->list_date->format('Y-m-d')}-{$causeList->judge->user->name}.pdf");
    }
}
