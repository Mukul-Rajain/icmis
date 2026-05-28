@extends('layouts.app')

@section('title', "Today's Hearings – DCFM")

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8">

    <div class="border-b border-stone-200 pb-5 mb-8 flex justify-between items-end">
        <div>
            <p class="text-xs uppercase tracking-widest text-stone-400 mb-2">Hearings</p>
            <h1 class="text-4xl font-display font-semibold text-stone-900">Scheduled Hearings</h1>
            <p class="text-stone-500 mt-1">{{ $hearings->total() }} hearings found</p>
        </div>
        {{-- Date filter --}}
        <form method="GET" class="flex items-center gap-3">
            <label class="text-xs uppercase tracking-wider text-stone-500">Date:</label>
            <input type="date" name="date" value="{{ request('date', today()->toDateString()) }}"
                class="border border-stone-300 px-3 py-1.5 text-sm" onchange="this.form.submit()">
        </form>
    </div>

    <div class="bg-white border border-stone-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-stone-900 text-stone-100">
                    <th class="text-left px-5 py-3 text-xs uppercase tracking-wider font-medium">Time</th>
                    <th class="text-left px-5 py-3 text-xs uppercase tracking-wider font-medium">Case</th>
                    <th class="text-left px-5 py-3 text-xs uppercase tracking-wider font-medium">Judge</th>
                    <th class="text-left px-5 py-3 text-xs uppercase tracking-wider font-medium">Track</th>
                    <th class="text-left px-5 py-3 text-xs uppercase tracking-wider font-medium">Status</th>
                    @if(auth()->user()->can('record-hearing-outcome'))
                    <th class="text-right px-5 py-3 text-xs uppercase tracking-wider font-medium">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($hearings as $hearing)
                <tr class="border-t border-stone-100 hover:bg-stone-50">
                    <td class="px-5 py-3 font-mono text-xs text-stone-500">
                        {{ $hearing->scheduled_time ?? '–' }}
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('cases.show', $hearing->case) }}"
                           class="font-mono text-xs text-stone-400 block">{{ $hearing->case->case_number }}</a>
                        <span class="text-stone-900 font-medium">{{ Str::limit($hearing->case->title, 55) }}</span>
                    </td>
                    <td class="px-5 py-3 text-stone-600 text-xs">
                        {{ $hearing->judge->user->name ?? '–' }}
                        <span class="block text-stone-400">{{ $hearing->courtroom_number }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5
                            @if($hearing->case->track === 'fast') bg-red-50 text-red-700 border border-red-200
                            @elseif($hearing->case->track === 'standard') bg-yellow-50 text-yellow-700 border border-yellow-200
                            @else bg-sky-50 text-sky-700 border border-sky-200 @endif">
                            {{ ucfirst($hearing->case->track) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded
                            @if($hearing->status === 'completed') bg-green-50 text-green-700 border border-green-200
                            @elseif($hearing->status === 'adjourned') bg-amber-50 text-amber-700 border border-amber-200
                            @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                            {{ ucfirst($hearing->status) }}
                        </span>
                    </td>
                    @if(auth()->user()->can('record-hearing-outcome'))
                    <td class="px-5 py-3 text-right">
                        @if($hearing->status === 'scheduled')
                        <a href="{{ route('hearings.outcome', $hearing) }}"
                           class="text-xs text-stone-600 underline">Record Outcome</a>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-stone-400">
                        No hearings scheduled for this date.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($hearings->hasPages())
    <div class="mt-6">{{ $hearings->links() }}</div>
    @endif
</div>
@endsection
