@extends('layouts.app')
@section('title', 'Cause Lists')

@section('content')
<div class="space-y-6">
    <div class="flex items-end justify-between border-b border-stone-300 pb-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Daily Listings</p>
            <h1 class="font-display text-4xl font-semibold text-stone-900">Cause Lists</h1>
        </div>
        @can('generate-cause-list')
        <a href="{{ route('cause-lists.generate.form') }}" class="px-4 py-2 bg-stone-900 text-white text-sm rounded-sm hover:bg-stone-800">
            Generate New
        </a>
        @endcan
    </div>

    <form method="GET" class="bg-white border border-stone-200 p-3 rounded-sm flex gap-3 items-center text-sm">
        <label class="text-xs uppercase tracking-wider text-stone-600">For Date:</label>
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="px-3 py-1.5 border border-stone-300 rounded-sm">
        <button class="px-4 py-1.5 bg-stone-900 text-white rounded-sm">View</button>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($lists as $list)
        <a href="{{ route('cause-lists.show', $list) }}" class="block bg-white border border-stone-200 p-5 rounded-sm hover:border-stone-400 transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-display text-lg font-semibold text-stone-900">{{ $list->judge->user->name }}</p>
                    <p class="text-sm text-stone-600">{{ $list->court->name }} · Courtroom {{ $list->judge->courtroom_number }}</p>
                </div>
                <span class="px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm
                    @if($list->status === 'published') bg-emerald-100 text-emerald-900
                    @elseif($list->status === 'draft') bg-amber-100 text-amber-900
                    @else bg-stone-100 @endif">
                    {{ $list->status }}
                </span>
            </div>
            <div class="mt-4 flex gap-6 text-sm text-stone-600">
                <span><strong class="text-stone-900">{{ $list->total_cases }}</strong> cases</span>
                <span>Generated {{ $list->generated_at?->diffForHumans() }}</span>
            </div>
        </a>
        @empty
        <div class="lg:col-span-2 bg-white border border-stone-200 p-12 rounded-sm text-center text-stone-500 text-sm italic">
            No cause lists for {{ $date->format('d M Y') }}.
            @can('generate-cause-list')
                <a href="{{ route('cause-lists.generate.form') }}" class="text-stone-900 underline">Generate one</a>
            @endcan
        </div>
        @endforelse
    </div>
</div>
@endsection
