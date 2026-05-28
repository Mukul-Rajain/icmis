@extends('layouts.app')

@section('title', 'Case Timeline – ' . $case->case_number)

@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('cases.show', $case) }}" class="text-xs uppercase tracking-wider text-stone-400 hover:text-stone-600">
            ← Back to Case
        </a>
        <h1 class="font-display text-3xl font-semibold text-stone-900 mt-3">Case Timeline</h1>
        <p class="text-stone-500 mt-1 font-mono text-sm">{{ $case->case_number }} · {{ $case->title }}</p>
    </div>

    {{-- Timeline --}}
    <div class="relative">
        {{-- Vertical line --}}
        <div class="absolute left-5 top-0 bottom-0 w-px bg-stone-200"></div>

        <div class="space-y-0">
            {{-- Filing event --}}
            <div class="relative flex gap-6 pb-8">
                <div class="w-10 h-10 rounded-full bg-stone-900 border-2 border-stone-900 flex items-center justify-center flex-shrink-0 z-10">
                    <span class="text-white text-xs">📁</span>
                </div>
                <div class="bg-white border border-stone-200 flex-1 p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-stone-900">Case Filed</p>
                            <p class="text-sm text-stone-500 mt-1">{{ $case->case_number }} registered in the system</p>
                        </div>
                        <span class="text-xs text-stone-400 font-mono">{{ $case->filing_date->format('d M Y') }}</span>
                    </div>
                    <div class="mt-2 flex gap-4 text-xs text-stone-400">
                        <span>Type: {{ $case->caseType->name }}</span>
                        <span>Court: {{ $case->court->name }}</span>
                        <span class="text-xs px-2 py-0.5
                            @if($case->track === 'fast') bg-red-50 text-red-700 border border-red-200
                            @elseif($case->track === 'standard') bg-yellow-50 text-yellow-700 border border-yellow-200
                            @else bg-sky-50 text-sky-700 border border-sky-200 @endif">
                            {{ ucfirst($case->track) }} Track
                        </span>
                    </div>
                </div>
            </div>

            {{-- Hearings --}}
            @foreach($case->hearings as $hearing)
            <div class="relative flex gap-6 pb-8">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-2
                    @if($hearing->status === 'completed') bg-green-600 border-green-600
                    @elseif($hearing->status === 'adjourned') bg-amber-500 border-amber-500
                    @else bg-blue-500 border-blue-500 @endif">
                    <span class="text-white text-xs">
                        @if($hearing->status === 'completed') ✓
                        @elseif($hearing->status === 'adjourned') ↩
                        @else ○ @endif
                    </span>
                </div>
                <div class="bg-white border border-stone-200 flex-1 p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-stone-900">
                                Hearing
                                @if($hearing->status === 'adjourned')
                                    <span class="ml-2 text-xs bg-amber-100 text-amber-700 px-2 py-0.5">Adjourned</span>
                                @elseif($hearing->status === 'completed')
                                    <span class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5">Completed</span>
                                @else
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5">Scheduled</span>
                                @endif
                            </p>
                            @if($hearing->outcome)
                                <p class="text-sm text-stone-600 mt-1">{{ $hearing->outcome }}</p>
                            @endif
                            @if($hearing->adjournments->count() > 0)
                                <p class="text-xs text-amber-600 mt-2">
                                    Reason: {{ $hearing->adjournments->first()->reason }}
                                </p>
                            @endif
                        </div>
                        <span class="text-xs text-stone-400 font-mono">{{ $hearing->scheduled_date->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Priority score history --}}
            @if($case->priorityScoreHistory->count() > 1)
            <div class="relative flex gap-6 pb-8">
                <div class="w-10 h-10 rounded-full bg-amber-700 border-2 border-amber-700 flex items-center justify-center flex-shrink-0 z-10">
                    <span class="text-white text-xs">⚡</span>
                </div>
                <div class="bg-amber-50 border border-amber-200 flex-1 p-4">
                    <p class="font-semibold text-amber-900">Priority Score History</p>
                    <div class="mt-2 space-y-1">
                        @foreach($case->priorityScoreHistory as $history)
                        <div class="flex justify-between text-xs text-amber-800">
                            <span>{{ $history->computed_at->format('d M Y H:i') }}</span>
                            <span class="font-mono font-semibold">{{ number_format($history->score, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Disposal --}}
            @if($case->status === 'disposed')
            <div class="relative flex gap-6 pb-8">
                <div class="w-10 h-10 rounded-full bg-stone-900 border-2 border-stone-900 flex items-center justify-center flex-shrink-0 z-10">
                    <span class="text-white text-xs">✓</span>
                </div>
                <div class="bg-stone-900 text-white flex-1 p-4">
                    <p class="font-semibold">Case Disposed</p>
                    <p class="text-sm text-stone-300 mt-1">{{ $case->disposed_on?->format('d M Y') }}</p>
                    @if($case->disposal_remarks)
                        <p class="text-sm text-stone-300 mt-1">{{ $case->disposal_remarks }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
