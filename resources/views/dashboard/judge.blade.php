@extends('layouts.app')
@section('title', "Judge's Bench")

@section('content')
<div class="space-y-8">

    <div class="border-b border-stone-300 pb-4">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Judge's Bench</p>
        <h1 class="font-display text-4xl font-semibold text-stone-900">{{ $judge->user->name }}</h1>
        <p class="text-sm text-stone-600 mt-1">{{ $judge->court->name }} · Courtroom {{ $judge->courtroom_number }}</p>
    </div>

    {{-- Stats strip --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Assigned Cases</p>
            <p class="font-display text-3xl font-semibold mt-1">{{ $stats['total_assigned'] }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Fast Track</p>
            <p class="font-display text-3xl font-semibold text-red-800 mt-1">{{ $stats['fast_track'] }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Overdue</p>
            <p class="font-display text-3xl font-semibold text-red-700 mt-1">{{ $stats['overdue'] }}</p>
        </div>
        <div class="bg-white border border-stone-200 p-5 rounded-sm">
            <p class="text-xs uppercase tracking-wider text-stone-500">Upcoming Hearings</p>
            <p class="font-display text-3xl font-semibold mt-1">{{ $stats['pending_hearings'] }}</p>
        </div>
    </div>

    {{-- Today's cause list --}}
    <div class="bg-white border border-stone-200 rounded-sm">
        <div class="double-rule px-6 pt-6 pb-4 flex items-end justify-between">
            <div>
                <h2 class="font-display text-2xl font-semibold text-stone-900">Today's Cause List</h2>
                <p class="text-sm text-stone-600">{{ now()->format('l, d F Y') }}</p>
            </div>
            @if($todayList)
                <a href="{{ route('cause-lists.show', $todayList) }}" class="text-xs uppercase tracking-wider text-stone-700 hover:text-stone-900 underline">
                    View full list →
                </a>
            @endif
        </div>

        @if(! $todayList)
            <div class="px-6 py-12 text-center text-stone-500 text-sm italic">
                No cause list generated for today.
                <a href="{{ route('cause-lists.generate.form') }}" class="text-stone-900 underline">Generate one now</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-600">
                    <tr>
                        <th class="text-left px-6 py-3 w-12">#</th>
                        <th class="text-left px-6 py-3">Time</th>
                        <th class="text-left px-6 py-3">Case</th>
                        <th class="text-left px-6 py-3">Track</th>
                        <th class="text-right px-6 py-3">Priority</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach($todayList->items as $item)
                    <tr class="hover:bg-stone-50">
                        <td class="px-6 py-3 font-mono text-stone-500">{{ $item->serial_number }}</td>
                        <td class="px-6 py-3 font-mono text-xs">{{ \Carbon\Carbon::parse($item->estimated_time_slot)->format('H:i') }}</td>
                        <td class="px-6 py-3">
                            <a href="{{ route('cases.show', $item->case) }}" class="text-stone-900 hover:underline">
                                <span class="font-mono text-xs">{{ $item->case->case_number }}</span>
                                <span class="text-stone-600 ml-2">{{ Str::limit($item->case->title, 40) }}</span>
                            </a>
                        </td>
                        <td class="px-6 py-3">
                            <span class="track-{{ $item->track_at_listing }} px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm">
                                {{ $item->track_at_listing }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right font-mono">{{ number_format($item->priority_score_at_listing, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- At-risk cases for this judge --}}
    @if($atRisk->isNotEmpty())
    <div class="bg-amber-50 border-l-4 border-amber-700 rounded-sm">
        <div class="px-6 py-4 border-b border-amber-200">
            <h3 class="font-display text-lg font-semibold text-amber-900">⚠ Cases Needing Attention</h3>
            <p class="text-xs text-amber-800">These cases are at risk of breaching their disposal timeline</p>
        </div>
        <div class="divide-y divide-amber-200">
            @foreach($atRisk as $assessment)
            @php $case = $assessment['case']; @endphp
            <div class="px-6 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('cases.show', $case) }}" class="font-mono text-xs text-amber-900 hover:underline">
                        {{ $case->case_number }}
                    </a>
                    <span class="text-stone-700 text-sm ml-3">{{ Str::limit($case->title, 50) }}</span>
                    <p class="text-xs text-amber-700 mt-1">{{ $assessment['reasons'][0] ?? '' }}</p>
                </div>
                <span class="px-2 py-0.5 text-xs uppercase rounded-sm
                    @if($assessment['level'] === 'overdue') bg-red-700 text-white
                    @elseif($assessment['level'] === 'at_risk') bg-amber-700 text-white
                    @else bg-amber-200 text-amber-900
                    @endif">
                    {{ str_replace('_', ' ', $assessment['level']) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
