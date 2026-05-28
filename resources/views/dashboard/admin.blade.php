@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    {{-- ─── Header ─── --}}
    <div class="flex items-end justify-between border-b border-stone-300 pb-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Operational Overview</p>
            <h1 class="font-display text-4xl font-semibold text-stone-900">Court Management Dashboard</h1>
            <p class="text-sm text-stone-600 mt-1">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('cases.create') }}" class="px-4 py-2 bg-stone-900 text-white text-sm hover:bg-stone-800 rounded-sm">
                Register New Case
            </a>
            <a href="{{ route('cause-lists.generate.form') }}" class="px-4 py-2 bg-amber-700 text-white text-sm hover:bg-amber-800 rounded-sm">
                Generate Cause List
            </a>
        </div>
    </div>

    {{-- ─── KPI cards ─── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Active Cases</p>
            <p class="font-display text-4xl font-semibold text-stone-900 mt-2">{{ $stats['total_active'] }}</p>
            <p class="text-xs text-stone-500 mt-1">in the system</p>
        </div>

        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Fast Track</p>
            <p class="font-display text-4xl font-semibold text-red-800 mt-2">{{ $stats['fast_track'] }}</p>
            <p class="text-xs text-stone-500 mt-1">requiring priority</p>
        </div>

        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Overdue</p>
            <p class="font-display text-4xl font-semibold text-red-700 mt-2">{{ $stats['overdue'] }}</p>
            <p class="text-xs text-stone-500 mt-1">past disposal date</p>
        </div>

        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Hearings Today</p>
            <p class="font-display text-4xl font-semibold text-stone-900 mt-2">{{ $stats['hearings_today'] }}</p>
            <p class="text-xs text-stone-500 mt-1">scheduled</p>
        </div>

        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Disposed This Month</p>
            <p class="font-display text-4xl font-semibold text-emerald-700 mt-2">{{ $stats['total_disposed_this_month'] }}</p>
            <p class="text-xs text-stone-500 mt-1">cases closed</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── Track distribution ─── --}}
        <div class="bg-white border border-stone-200 p-6 rounded-sm">
            <h3 class="font-display text-lg font-semibold text-stone-900 mb-4">Track Distribution</h3>

            @php
                $total = $trackBreakdown->sum();
                $fastPct = $total > 0 ? round(($trackBreakdown['fast'] ?? 0) / $total * 100) : 0;
                $standardPct = $total > 0 ? round(($trackBreakdown['standard'] ?? 0) / $total * 100) : 0;
                $complexPct = $total > 0 ? round(($trackBreakdown['complex'] ?? 0) / $total * 100) : 0;
            @endphp

            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-red-800 font-medium">Fast Track</span>
                        <span class="font-mono text-stone-600">{{ $trackBreakdown['fast'] ?? 0 }} · {{ $fastPct }}%</span>
                    </div>
                    <div class="h-2 bg-stone-100 rounded-sm overflow-hidden">
                        <div class="h-full bg-red-700" style="width: {{ $fastPct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-amber-800 font-medium">Standard</span>
                        <span class="font-mono text-stone-600">{{ $trackBreakdown['standard'] ?? 0 }} · {{ $standardPct }}%</span>
                    </div>
                    <div class="h-2 bg-stone-100 rounded-sm overflow-hidden">
                        <div class="h-full bg-amber-600" style="width: {{ $standardPct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-sky-800 font-medium">Complex</span>
                        <span class="font-mono text-stone-600">{{ $trackBreakdown['complex'] ?? 0 }} · {{ $complexPct }}%</span>
                    </div>
                    <div class="h-2 bg-stone-100 rounded-sm overflow-hidden">
                        <div class="h-full bg-sky-700" style="width: {{ $complexPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── At-risk alert ─── --}}
        <div class="bg-amber-50 border-l-4 border-amber-700 p-6 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-amber-800">Delay Predictor</p>
            <p class="font-display text-5xl font-semibold text-amber-900 mt-2">{{ $atRiskCount }}</p>
            <p class="text-sm text-amber-800 mt-1">cases flagged at-risk of breaching their timeline</p>
            <a href="{{ route('analytics.at-risk') }}" class="inline-block mt-3 text-xs uppercase tracking-wider text-amber-900 underline hover:text-amber-700">
                Review at-risk cases →
            </a>
        </div>

        {{-- ─── Quick actions ─── --}}
        <div class="bg-stone-900 text-stone-100 p-6 rounded-sm">
            <p class="text-xs uppercase tracking-widest text-stone-400 mb-3">Quick Access</p>
            <div class="space-y-2">
                <a href="{{ route('cases.index') }}" class="block py-2 text-stone-100 hover:text-amber-300 text-sm">
                    → Browse all cases
                </a>
                <a href="{{ route('hearings.index') }}" class="block py-2 text-stone-100 hover:text-amber-300 text-sm">
                    → Today's hearings
                </a>
                <a href="{{ route('cause-lists.index') }}" class="block py-2 text-stone-100 hover:text-amber-300 text-sm">
                    → Cause lists
                </a>
                <a href="{{ route('analytics') }}" class="block py-2 text-stone-100 hover:text-amber-300 text-sm">
                    → Analytics & reports
                </a>
            </div>
        </div>
    </div>

    {{-- ─── Recent cases ─── --}}
    <div class="bg-white border border-stone-200 rounded-sm">
        <div class="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-stone-900">Recent Filings</h3>
            <a href="{{ route('cases.index') }}" class="text-xs uppercase tracking-wider text-stone-600 hover:text-stone-900">View all →</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Case Number</th>
                    <th class="text-left px-6 py-3 font-medium">Title</th>
                    <th class="text-left px-6 py-3 font-medium">Type</th>
                    <th class="text-left px-6 py-3 font-medium">Track</th>
                    <th class="text-right px-6 py-3 font-medium">Priority</th>
                    <th class="text-left px-6 py-3 font-medium">Filed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach($recentCases as $case)
                <tr class="hover:bg-stone-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('cases.show', $case) }}" class="font-mono text-xs text-stone-900 hover:underline">
                            {{ $case->case_number }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-stone-700">{{ Str::limit($case->title, 40) }}</td>
                    <td class="px-6 py-3 text-stone-600 text-xs">{{ $case->caseType->name }}</td>
                    <td class="px-6 py-3">
                        <span class="track-{{ $case->track }} px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm">
                            {{ $case->track }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right font-mono text-stone-900">{{ number_format($case->priority_score, 1) }}</td>
                    <td class="px-6 py-3 text-stone-500 text-xs">{{ $case->filing_date->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
