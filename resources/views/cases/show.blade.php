@extends('layouts.app')
@section('title', $case->case_number)

@section('content')
<div class="space-y-6">

    {{-- ─── Header ─── --}}
    <div class="bg-white border border-stone-200 rounded-sm">
        <div class="double-rule px-6 pt-6 pb-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-xs text-stone-500 mb-1">{{ $case->case_number }}</p>
                    <h1 class="font-display text-3xl font-semibold text-stone-900">{{ $case->title }}</h1>
                    <p class="text-sm text-stone-600 mt-2">
                        {{ $case->caseType->name }} · {{ $case->court->name }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="track-{{ $case->track }} px-3 py-1 text-xs uppercase tracking-wider rounded-sm">
                        {{ $case->track }} Track
                    </span>
                    <div class="mt-3">
                        <p class="text-xs uppercase tracking-wider text-stone-500">Priority Score</p>
                        <p class="font-display text-4xl font-semibold text-stone-900">{{ number_format($case->priority_score, 1) }}</p>
                    </div>
                </div>
            </div>

            {{-- Risk badge --}}
            @if($riskAssessment['level'] !== 'safe')
            <div class="mt-4 px-4 py-2 inline-flex items-center gap-2 rounded-sm
                @if($riskAssessment['level'] === 'overdue') bg-red-100 text-red-900
                @elseif($riskAssessment['level'] === 'at_risk') bg-amber-100 text-amber-900
                @else bg-yellow-50 text-yellow-900
                @endif">
                <span class="text-xs uppercase tracking-wider font-semibold">{{ str_replace('_', ' ', $riskAssessment['level']) }}</span>
                @if(! empty($riskAssessment['reasons']))
                    <span class="text-xs">· {{ $riskAssessment['reasons'][0] }}</span>
                @endif
            </div>
            @endif
        </div>

        <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 border-t border-stone-100 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Filed On</p>
                <p class="text-stone-900 mt-1">{{ $case->filing_date->format('d M Y') }}</p>
                <p class="text-xs text-stone-500">{{ $case->age_in_days }} days ago</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Current Stage</p>
                <p class="text-stone-900 mt-1 capitalize">{{ str_replace('_', ' ', $case->current_stage) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Hearings / Adjournments</p>
                <p class="text-stone-900 mt-1 font-mono">{{ $case->hearing_count }} / {{ $case->adjournment_count }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Expected Disposal</p>
                <p class="text-stone-900 mt-1">{{ $case->expected_disposal_date?->format('d M Y') ?? '—' }}</p>
                @if($case->expected_disposal_date)
                    <p class="text-xs @if($case->is_overdue) text-red-700 @else text-stone-500 @endif">
                        {{ $case->days_until_expected_disposal > 0 ? $case->days_until_expected_disposal . ' days remaining' : abs($case->days_until_expected_disposal) . ' days overdue' }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- ─── Parties ─── --}}
            <div class="bg-white border border-stone-200 rounded-sm">
                <div class="px-6 py-4 border-b border-stone-200">
                    <h3 class="font-display text-lg font-semibold text-stone-900">Parties</h3>
                </div>
                <div class="divide-y divide-stone-100">
                    @foreach($case->parties as $party)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-stone-500">{{ str_replace('_', ' ', $party->party_type) }}</p>
                            <p class="text-stone-900 mt-0.5">{{ $party->name }}</p>
                        </div>
                        <div class="flex gap-2">
                            @if($party->is_senior_citizen)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-900 text-xs rounded-sm">Senior Citizen</span>
                            @endif
                            @if($party->is_in_custody)
                                <span class="px-2 py-0.5 bg-red-100 text-red-900 text-xs rounded-sm">In Custody</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ─── Hearings timeline ─── --}}
            <div class="bg-white border border-stone-200 rounded-sm">
                <div class="px-6 py-4 border-b border-stone-200">
                    <h3 class="font-display text-lg font-semibold text-stone-900">Hearings & Adjournments</h3>
                </div>
                @if($case->hearings->isEmpty())
                    <div class="px-6 py-8 text-center text-stone-500 text-sm italic">No hearings recorded yet</div>
                @else
                <div class="divide-y divide-stone-100">
                    @foreach($case->hearings as $hearing)
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="text-stone-900 font-medium">{{ $hearing->scheduled_date->format('d M Y') }}
                                    @if($hearing->scheduled_time)
                                        <span class="text-stone-500">· {{ \Carbon\Carbon::parse($hearing->scheduled_time)->format('H:i') }}</span>
                                    @endif
                                </p>
                                @if($hearing->outcome)
                                    <p class="text-xs text-stone-600 mt-0.5">{{ Str::limit($hearing->outcome, 100) }}</p>
                                @endif
                            </div>
                            <span class="px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm
                                @if($hearing->status === 'completed') bg-emerald-100 text-emerald-900
                                @elseif($hearing->status === 'adjourned') bg-amber-100 text-amber-900
                                @elseif($hearing->status === 'scheduled') bg-sky-100 text-sky-900
                                @else bg-stone-100 text-stone-700 @endif">
                                {{ $hearing->status }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ─── Priority Score Breakdown (the showcase) ─── --}}
        <div class="lg:col-span-1">
            <div class="bg-stone-900 text-stone-100 rounded-sm overflow-hidden sticky top-6">
                <div class="px-6 pt-6 pb-4 border-b border-stone-700">
                    <p class="text-xs uppercase tracking-widest text-stone-400 mb-1">DCFM Algorithm</p>
                    <h3 class="font-display text-2xl font-semibold">Score Breakdown</h3>
                    <p class="text-xs text-stone-400 mt-1">How this case got priority {{ number_format($case->priority_score, 1) }}</p>
                </div>

                @if($latestScoreFactors)
                <div class="p-6 space-y-3 text-sm">
                    @php
                        $factors = [
                            'base_priority' => ['Base Priority', 'Set by case type'],
                            'age_contribution' => ['Age Factor', 'Older cases prioritised'],
                            'urgency_contribution' => ['Urgency Factor', 'Interim relief / time-sensitive'],
                            'adjournment_contribution' => ['Adjournment Factor', 'Cases stuck need listing'],
                            'stage_contribution' => ['Stage Factor', 'Closer to disposal = priority'],
                            'stakeholder_contribution' => ['Stakeholder Factor', 'Senior citizens / in-custody'],
                        ];
                    @endphp
                    @foreach($factors as $key => [$label, $desc])
                        @if(isset($latestScoreFactors[$key]))
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-stone-800 last:border-0">
                            <div>
                                <p class="text-stone-200 font-medium">{{ $label }}</p>
                                <p class="text-xs text-stone-500">{{ $desc }}</p>
                            </div>
                            <span class="font-mono text-amber-400">+{{ number_format($latestScoreFactors[$key], 2) }}</span>
                        </div>
                        @endif
                    @endforeach
                </div>

                <div class="bg-amber-700 text-amber-50 px-6 py-3 text-sm flex justify-between">
                    <span>Final Score</span>
                    <span class="font-display font-semibold">{{ number_format($case->priority_score, 2) }}</span>
                </div>
                @else
                <div class="p-6 text-sm text-stone-400">
                    No score history yet.
                    <form method="POST" action="{{ route('cases.rescore', $case) }}" class="mt-3">
                        @csrf
                        <button class="text-amber-400 underline text-xs">Compute now</button>
                    </form>
                </div>
                @endif

                <div class="bg-stone-800 px-6 py-3 text-xs text-stone-400">
                    Last computed: {{ $case->priorityScoreHistory->first()?->computed_at?->diffForHumans() ?? 'never' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
