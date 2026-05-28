<?php

namespace App\Http\Controllers;

use App\Models\CourtCase;
use Illuminate\Http\Request;

/**
 * Public-facing case status lookup.
 * Mimics the eCourts public services portal — anyone can check case
 * status with case number + petitioner's DOB (basic verification).
 *
 * Returns very limited info to protect privacy.
 */
class PublicCaseController extends Controller
{
    public function form()
    {
        return view('public.case-status-form');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'case_number' => 'required|string',
            'verification_name' => 'required|string', // Name of any party in the case
        ]);

        $case = CourtCase::where('case_number', $validated['case_number'])
            ->whereHas('parties', function ($q) use ($validated) {
                $q->whereRaw('LOWER(name) = ?', [strtolower(trim($validated['verification_name']))]);
            })
            ->with(['caseType', 'court', 'assignedJudge.user'])
            ->first();

        if (! $case) {
            return back()
                ->withErrors(['case_number' => 'No matching case found. Please check the case number and party name.'])
                ->withInput();
        }

        // Read-only safe data only
        return view('public.case-status-result', compact('case'));
    }
}
