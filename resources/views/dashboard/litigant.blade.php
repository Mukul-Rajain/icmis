@extends('layouts.app')

@section('title', 'My Cases – DCFM')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="border-b border-stone-200 pb-5 mb-8">
        <p class="text-xs uppercase tracking-widest text-stone-400 mb-2">Litigant Portal</p>
        <h1 class="text-4xl font-display font-semibold text-stone-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-stone-500 mt-1">Track the status and hearing schedule of your cases below.</p>
    </div>

    @if($cases->isEmpty())
    <div class="bg-white border border-stone-200 px-8 py-16 text-center">
        <p class="text-5xl mb-4">⚖️</p>
        <h2 class="font-display text-xl font-semibold text-stone-700 mb-2">No Cases Found</h2>
        <p class="text-stone-500 text-sm">You are not currently listed as a party in any active cases.</p>
        <p class="text-stone-400 text-xs mt-3">Contact the court registrar if you believe this is an error.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($cases as $case)
        <div class="bg-white border border-stone-200 hover:border-stone-400 transition">
            <div class="px-6 py-5">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="font-mono text-xs text-stone-400">{{ $case->case_number }}</span>
                            <span class="text-xs px-2 py-0.5
                                @if($case->track === 'fast') bg-red-50 text-red-700 border border-red-200
                                @elseif($case->track === 'standard') bg-yellow-50 text-yellow-700 border border-yellow-200
                                @else bg-sky-50 text-sky-700 border border-sky-200 @endif">
                                {{ ucfirst($case->track) }} Track
                            </span>
                        </div>
                        <h3 class="font-semibold text-stone-900 text-lg">{{ $case->title }}</h3>
                        <p class="text-stone-500 text-sm mt-1">
                            {{ $case->caseType->name }} · {{ $case->court->name }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-stone-400 uppercase tracking-wider">Stage</p>
                        <p class="font-medium text-stone-700 capitalize mt-1">
                            {{ str_replace('_', ' ', $case->current_stage) }}
                        </p>
                        @if($case->assignedJudge)
                        <p class="text-xs text-stone-400 mt-2">{{ $case->assignedJudge->user->name }}</p>
                        @endif
                    </div>
                </div>

                {{-- Next hearing if any --}}
                @php
                    $nextHearing = $case->hearings()
                        ->where('status', 'scheduled')
                        ->whereDate('scheduled_date', '>=', now())
                        ->orderBy('scheduled_date')
                        ->first();
                @endphp
                @if($nextHearing)
                <div class="mt-4 pt-4 border-t border-stone-100 flex items-center gap-3">
                    <span class="text-xs text-stone-400 uppercase tracking-wider">Next Hearing:</span>
                    <span class="text-sm font-medium text-stone-700">
                        {{ $nextHearing->scheduled_date->format('D, d M Y') }}
                        @if($nextHearing->scheduled_time) at {{ $nextHearing->scheduled_time }} @endif
                    </span>
                    <span class="text-xs text-stone-400">{{ $nextHearing->court->name ?? '' }}</span>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="mt-8 bg-amber-50 border border-amber-200 px-6 py-4 text-sm text-amber-800">
        <strong>Public case lookup:</strong>
        You can also check case status without logging in at
        <a href="{{ route('public.case-status') }}" class="underline">public case search</a>.
    </div>
</div>
@endsection
