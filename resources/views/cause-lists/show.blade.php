@extends('layouts.app')
@section('title', 'Cause List ' . $causeList->list_date->format('d M Y'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white border border-stone-200 rounded-sm">
        <div class="double-rule px-6 pt-6 pb-4 flex items-start justify-between">
            <div>
                <p class="text-xs uppercase tracking-widest text-stone-500 mb-1">Cause List</p>
                <h1 class="font-display text-3xl font-semibold text-stone-900">{{ $causeList->list_date->format('l, d F Y') }}</h1>
                <p class="text-sm text-stone-600 mt-1">
                    {{ $causeList->judge->user->name }} · {{ $causeList->court->name }} · Courtroom {{ $causeList->judge->courtroom_number }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="px-3 py-1 text-xs uppercase tracking-wider rounded-sm
                    @if($causeList->status === 'published') bg-emerald-100 text-emerald-900
                    @elseif($causeList->status === 'draft') bg-amber-100 text-amber-900
                    @else bg-stone-100 text-stone-700 @endif">
                    {{ $causeList->status }}
                </span>

                <div class="flex gap-2">
                    <a href="{{ route('cause-lists.pdf', $causeList) }}" class="px-3 py-1.5 bg-stone-200 text-stone-800 text-xs hover:bg-stone-300 rounded-sm">
                        Download PDF
                    </a>
                    @if($causeList->status === 'draft' && auth()->user()->can('publish-cause-list'))
                    <form method="POST" action="{{ route('cause-lists.publish', $causeList) }}">
                        @csrf
                        <button class="px-3 py-1.5 bg-stone-900 text-white text-xs hover:bg-stone-800 rounded-sm">
                            Publish
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-stone-100 grid grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Total Cases</p>
                <p class="font-display text-xl font-semibold mt-1">{{ $causeList->total_cases }}</p>
            </div>
            @php
                $trackCounts = $causeList->items->groupBy('track_at_listing')->map->count();
            @endphp
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Fast Track</p>
                <p class="font-display text-xl font-semibold text-red-800 mt-1">{{ $trackCounts['fast'] ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Standard</p>
                <p class="font-display text-xl font-semibold text-amber-800 mt-1">{{ $trackCounts['standard'] ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-stone-500">Complex</p>
                <p class="font-display text-xl font-semibold text-sky-800 mt-1">{{ $trackCounts['complex'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Conflict warnings from session flash --}}
    @if(session('conflicts') && count(session('conflicts')) > 0)
    <div class="bg-amber-50 border-l-4 border-amber-700 px-4 py-3 rounded-sm">
        <p class="text-sm font-semibold text-amber-900">⚠ Lawyer Conflicts Detected</p>
        <ul class="text-xs text-amber-800 mt-2 space-y-1">
            @foreach(session('conflicts') as $conflict)
                <li>Lawyer also has hearings at: 
                    @foreach($conflict['conflicting_hearings'] as $other)
                        {{ $other['court'] }} ({{ $other['case_number'] }})@if(! $loop->last), @endif
                    @endforeach
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- The list --}}
    <div class="bg-white border border-stone-200 rounded-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-900 text-stone-100 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 w-12">#</th>
                    <th class="text-left px-4 py-3 w-20">Time</th>
                    <th class="text-left px-4 py-3">Case</th>
                    <th class="text-left px-4 py-3">Petitioner v. Respondent</th>
                    <th class="text-left px-4 py-3 w-24">Stage</th>
                    <th class="text-left px-4 py-3 w-20">Track</th>
                    <th class="text-right px-4 py-3 w-20">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach($causeList->items as $item)
                @php $case = $item->case; @endphp
                <tr class="hover:bg-stone-50">
                    <td class="px-4 py-3 font-mono text-xs text-stone-500">{{ $item->serial_number }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ \Carbon\Carbon::parse($item->estimated_time_slot)->format('H:i') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('cases.show', $case) }}" class="font-mono text-xs text-stone-900 hover:underline">
                            {{ $case->case_number }}
                        </a>
                        <p class="text-xs text-stone-600 mt-0.5">{{ Str::limit($case->title, 40) }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-stone-700">
                        @php
                            $petitioner = $case->parties->whereIn('party_type', ['petitioner', 'plaintiff', 'complainant'])->first();
                            $respondent = $case->parties->whereIn('party_type', ['respondent', 'defendant', 'accused'])->first();
                        @endphp
                        {{ $petitioner?->name ?? '—' }}
                        <span class="text-stone-400 italic">v.</span>
                        {{ $respondent?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-stone-600 capitalize">{{ str_replace('_', ' ', $case->current_stage) }}</td>
                    <td class="px-4 py-3">
                        <span class="track-{{ $item->track_at_listing }} px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm">
                            {{ $item->track_at_listing }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono">{{ number_format($item->priority_score_at_listing, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-stone-500 text-center italic">
        Generated {{ $causeList->generated_at?->diffForHumans() }} by {{ $causeList->generatedBy?->name ?? 'System' }}
        · Cases ordered by track (fast → standard → complex) and priority score within each track
    </p>
</div>
@endsection
