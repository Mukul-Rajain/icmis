@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
<div class="space-y-8">

    <div class="border-b border-stone-300 pb-4">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Operational Analytics</p>
        <h1 class="font-display text-4xl font-semibold text-stone-900">DCFM Performance Insights</h1>
        <p class="text-sm text-stone-600 mt-1">Real-time metrics showing how Differentiated Case Flow Management is impacting court efficiency</p>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Active Cases</p>
            <p class="font-display text-4xl font-semibold mt-2">{{ number_format($kpis['total_active']) }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Disposed (Last 30 Days)</p>
            <p class="font-display text-4xl font-semibold text-emerald-700 mt-2">{{ number_format($kpis['disposed_last_30_days']) }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Overdue Cases</p>
            <p class="font-display text-4xl font-semibold text-red-700 mt-2">{{ number_format($kpis['overdue_cases']) }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Avg. Active Case Age</p>
            <p class="font-display text-4xl font-semibold mt-2">{{ $kpis['avg_age_active_days'] }}<span class="text-base text-stone-500"> days</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ─── Disposal time per track (the headline DCFM metric) ─── --}}
        <div class="bg-white border border-stone-200 p-6 rounded-sm">
            <h3 class="font-display text-lg font-semibold text-stone-900">Average Disposal Time by Track</h3>
            <p class="text-xs text-stone-500 mb-4">Demonstrates DCFM's impact — fast track cases close significantly faster</p>

            <div class="space-y-4 mt-6">
                @foreach(['fast' => 'Fast Track', 'standard' => 'Standard', 'complex' => 'Complex'] as $track => $label)
                @php
                    $data = $disposalByTrack[$track] ?? null;
                    $avgDays = $data ? round($data->avg_days) : 0;
                    $count = $data ? $data->count : 0;
                    $maxDays = $disposalByTrack->max('avg_days') ?: 1;
                    $pct = $maxDays > 0 ? ($avgDays / $maxDays) * 100 : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium
                            @if($track === 'fast') text-red-800
                            @elseif($track === 'standard') text-amber-800
                            @else text-sky-800 @endif">
                            {{ $label }}
                        </span>
                        <span class="font-mono text-stone-600">{{ $avgDays }} days · {{ $count }} disposed</span>
                    </div>
                    <div class="h-3 bg-stone-100 rounded-sm overflow-hidden">
                        <div class="h-full
                            @if($track === 'fast') bg-red-700
                            @elseif($track === 'standard') bg-amber-600
                            @else bg-sky-700 @endif"
                            style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <p class="text-xs text-stone-500 mt-6 italic">
                Lower is better. DCFM aims to keep fast-track disposal under {{ \App\Models\CaseType::TRACK_FAST }} sustained levels.
            </p>
        </div>

        {{-- ─── Track distribution ─── --}}
        <div class="bg-white border border-stone-200 p-6 rounded-sm">
            <h3 class="font-display text-lg font-semibold text-stone-900">Active Case Distribution</h3>
            <p class="text-xs text-stone-500 mb-4">Where the workload sits today</p>

            <canvas id="trackDonut" width="400" height="280"></canvas>
        </div>
    </div>

    {{-- ─── Pendency by case type ─── --}}
    <div class="bg-white border border-stone-200 p-6 rounded-sm">
        <h3 class="font-display text-lg font-semibold text-stone-900 mb-4">Top 10 Case Types by Pendency</h3>

        <div class="space-y-2">
            @php $maxPending = $pendencyByType->max('pending') ?: 1; @endphp
            @foreach($pendencyByType as $type)
            <div class="flex items-center gap-3">
                <div class="w-48 text-sm text-stone-700 truncate">{{ $type->name }}</div>
                <div class="flex-1 h-6 bg-stone-100 rounded-sm overflow-hidden relative">
                    <div class="h-full bg-stone-700" style="width: {{ ($type->pending / $maxPending) * 100 }}%"></div>
                    <span class="absolute inset-0 flex items-center px-2 text-xs font-mono text-white">{{ $type->pending }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ─── Disposal trend line chart ─── --}}
    <div class="bg-white border border-stone-200 p-6 rounded-sm">
        <h3 class="font-display text-lg font-semibold text-stone-900">Monthly Disposal Trends</h3>
        <p class="text-xs text-stone-500 mb-4">Cases disposed per month, broken down by track — last 12 months</p>
        <canvas id="trendChart" width="800" height="300"></canvas>
    </div>

    {{-- At-risk callout --}}
    @if($atRiskCount > 0)
    <div class="bg-amber-50 border-l-4 border-amber-700 p-6 rounded-sm flex items-center justify-between">
        <div>
            <p class="font-display text-2xl font-semibold text-amber-900">
                {{ $atRiskCount }} cases at risk
            </p>
            <p class="text-sm text-amber-800">These cases are likely to breach their track timeline. Review and prioritise listing.</p>
        </div>
        <a href="{{ route('analytics.at-risk') }}" class="px-4 py-2 bg-amber-700 text-white text-sm rounded-sm hover:bg-amber-800">
            Review →
        </a>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // ─── Donut: Track distribution ───────────────────────────
    new Chart(document.getElementById('trackDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Fast Track', 'Standard', 'Complex'],
            datasets: [{
                data: [
                    {{ $byTrack['fast'] ?? 0 }},
                    {{ $byTrack['standard'] ?? 0 }},
                    {{ $byTrack['complex'] ?? 0 }}
                ],
                backgroundColor: ['#b91c1c', '#d97706', '#0369a1'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 }, padding: 14 } },
            },
            cutout: '60%',
        },
    });

    // ─── Trend line chart ───────────────────────────────────
    fetch('{{ route('analytics.disposal') }}')
        .then(r => r.json())
        .then(months => {
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: months.map(m => m.month),
                    datasets: [
                        { label: 'Fast', data: months.map(m => m.fast), borderColor: '#b91c1c', backgroundColor: 'rgba(185,28,28,0.1)', tension: 0.3 },
                        { label: 'Standard', data: months.map(m => m.standard), borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,0.1)', tension: 0.3 },
                        { label: 'Complex', data: months.map(m => m.complex), borderColor: '#0369a1', backgroundColor: 'rgba(3,105,161,0.1)', tension: 0.3 },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Inter' } } },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { font: { family: 'Inter' } } },
                        x: { ticks: { font: { family: 'Inter' } } },
                    },
                },
            });
        });
</script>
@endsection
