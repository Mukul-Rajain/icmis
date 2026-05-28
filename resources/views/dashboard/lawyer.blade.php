@extends('layouts.app')

@section('title', 'My Cases – DCFM')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="border-b border-stone-200 pb-5 mb-8">
        <p class="text-xs uppercase tracking-widest text-stone-400 mb-2">Lawyer Dashboard</p>
        <h1 class="text-4xl font-display font-semibold text-stone-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-stone-500 mt-1">{{ $cases->count() }} active cases · {{ $upcomingHearings->count() }} upcoming hearings</p>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- Cases List --}}
        <div class="col-span-2">
            <div class="bg-white border border-stone-200">
                <div class="px-6 py-4 border-b border-stone-100 flex justify-between items-center">
                    <h2 class="font-display text-lg font-semibold">My Cases</h2>
                    <span class="text-xs text-stone-400 uppercase tracking-wider">Sorted by priority score</span>
                </div>

                @forelse($cases as $case)
                <div class="px-6 py-4 border-b border-stone-50 hover:bg-stone-50 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <a href="{{ route('cases.show', $case) }}"
                               class="font-mono text-xs text-stone-500">{{ $case->case_number }}</a>
                            <p class="font-medium text-stone-900 mt-1">{{ $case->title }}</p>
                            <p class="text-sm text-stone-500 mt-1">
                                {{ $case->caseType->name }} · {{ $case->court->name }}
                            </p>
                        </div>
                        <div class="text-right flex flex-col items-end gap-2">
                            <span class="track-pill text-xs px-2 py-0.5
                                @if($case->track === 'fast') bg-red-50 text-red-800 border border-red-200
                                @elseif($case->track === 'standard') bg-yellow-50 text-yellow-800 border border-yellow-200
                                @else bg-sky-50 text-sky-800 border border-sky-200 @endif">
                                {{ strtoupper($case->track) }}
                            </span>
                            <span class="font-mono text-lg font-semibold text-stone-700">{{ number_format($case->priority_score, 1) }}</span>
                            <span class="text-xs text-stone-400 capitalize">{{ str_replace('_', ' ', $case->current_stage) }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-stone-400">
                    <p class="text-4xl mb-3">📁</p>
                    <p>You have no active cases assigned.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Hearings --}}
        <div>
            <div class="bg-white border border-stone-200">
                <div class="px-5 py-4 border-b border-stone-100">
                    <h2 class="font-display text-lg font-semibold">Upcoming Hearings</h2>
                </div>

                @forelse($upcomingHearings as $hearing)
                <div class="px-5 py-4 border-b border-stone-50">
                    <p class="text-xs text-stone-400 uppercase tracking-wider">
                        {{ $hearing->scheduled_date->format('d M Y') }} · {{ $hearing->scheduled_time }}
                    </p>
                    <p class="font-medium text-sm text-stone-900 mt-1">{{ $hearing->case->title }}</p>
                    <p class="text-xs text-stone-500 mt-1">{{ $hearing->court->name }}</p>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-stone-400 text-sm">
                    No upcoming hearings scheduled.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
