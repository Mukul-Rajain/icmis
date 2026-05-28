@extends('layouts.app')
@section('title', 'Case Status')

@section('content')
<div class="max-w-2xl mx-auto py-8 space-y-6">

    <div class="bg-white border border-stone-200 rounded-sm">
        <div class="double-rule px-6 pt-6 pb-4">
            <p class="font-mono text-xs text-stone-500">{{ $case->case_number }}</p>
            <h1 class="font-display text-2xl font-semibold text-stone-900 mt-1">{{ $case->title }}</h1>
            <span class="track-{{ $case->track }} mt-3 inline-block px-3 py-1 text-xs uppercase tracking-wider rounded-sm">
                {{ $case->track }} Track
            </span>
        </div>

        <div class="px-6 py-4 grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Status</p>
                <p class="text-stone-900 capitalize mt-0.5">{{ $case->status }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Current Stage</p>
                <p class="text-stone-900 capitalize mt-0.5">{{ str_replace('_', ' ', $case->current_stage) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Court</p>
                <p class="text-stone-900 mt-0.5">{{ $case->court->name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Case Type</p>
                <p class="text-stone-900 mt-0.5">{{ $case->caseType->name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Filed On</p>
                <p class="text-stone-900 mt-0.5">{{ $case->filing_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Next Hearing</p>
                <p class="text-stone-900 mt-0.5">
                    {{ $case->next_hearing_date?->format('d M Y') ?? 'Not scheduled' }}
                </p>
            </div>
        </div>

        <div class="px-6 py-3 bg-stone-50 border-t border-stone-200 text-xs text-stone-500 italic">
            Note: For detailed case information including documents and orders, please contact the court registry.
        </div>
    </div>

    <a href="{{ route('public.case-status') }}" class="block text-center text-sm text-stone-600 underline hover:text-stone-900">
        Look up another case
    </a>
</div>
@endsection
