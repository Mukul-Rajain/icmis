@extends('layouts.app')
@section('title', 'At-Risk Cases')

@section('content')
<div class="space-y-6">
    <div class="border-b border-stone-300 pb-4">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Delay Predictor</p>
        <h1 class="font-display text-4xl font-semibold text-stone-900">Cases At Risk</h1>
        <p class="text-sm text-stone-600 mt-1">Statistical analysis flags these cases as likely to breach their track timeline. Prioritise listing.</p>
    </div>

    <div class="grid grid-cols-3 gap-4">
        @php
            $overdue = $atRiskCases->where('level', 'overdue')->count();
            $atRisk = $atRiskCases->where('level', 'at_risk')->count();
        @endphp
        <div class="bg-red-50 border-l-4 border-red-700 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-red-800">Overdue</p>
            <p class="font-display text-4xl font-semibold text-red-900 mt-1">{{ $overdue }}</p>
            <p class="text-xs text-red-700 mt-1">past expected disposal date</p>
        </div>
        <div class="bg-amber-50 border-l-4 border-amber-700 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-amber-800">At Risk</p>
            <p class="font-display text-4xl font-semibold text-amber-900 mt-1">{{ $atRisk }}</p>
            <p class="text-xs text-amber-700 mt-1">80%+ of timeline elapsed</p>
        </div>
        <div class="bg-stone-50 border-l-4 border-stone-700 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-700">Total Flagged</p>
            <p class="font-display text-4xl font-semibold text-stone-900 mt-1">{{ $atRiskCases->count() }}</p>
            <p class="text-xs text-stone-600 mt-1">need intervention</p>
        </div>
    </div>

    <div class="bg-white border border-stone-200 rounded-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-600">
                <tr>
                    <th class="text-left px-4 py-3">Case</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Age</th>
                    <th class="text-left px-4 py-3">% Elapsed</th>
                    <th class="text-left px-4 py-3">Adjournments</th>
                    <th class="text-left px-4 py-3">Risk Level</th>
                    <th class="text-left px-4 py-3">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($atRiskCases as $assessment)
                @php $case = $assessment['case']; @endphp
                <tr class="hover:bg-stone-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('cases.show', $case) }}" class="font-mono text-xs text-stone-900 hover:underline">
                            {{ $case->case_number }}
                        </a>
                        <p class="text-xs text-stone-600 mt-0.5">{{ Str::limit($case->title, 30) }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-stone-700">{{ $case->caseType->name }}</td>
                    <td class="px-4 py-3 text-xs font-mono">{{ $case->age_in_days }}d</td>
                    <td class="px-4 py-3 text-xs font-mono">{{ $assessment['percentage_elapsed'] }}%</td>
                    <td class="px-4 py-3 text-xs font-mono">{{ $case->adjournment_count }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm
                            @if($assessment['level'] === 'overdue') bg-red-700 text-white
                            @else bg-amber-700 text-white @endif">
                            {{ str_replace('_', ' ', $assessment['level']) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-stone-700">{{ $assessment['reasons'][0] ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-stone-500 italic">No cases currently at risk 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
