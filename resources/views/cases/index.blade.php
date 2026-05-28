@extends('layouts.app')
@section('title', 'Cases')

@section('content')
<div class="space-y-6">

    <div class="flex items-end justify-between border-b border-stone-300 pb-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Registry</p>
            <h1 class="font-display text-4xl font-semibold text-stone-900">All Cases</h1>
        </div>
        @can('register-case')
        <a href="{{ route('cases.create') }}" class="px-4 py-2 bg-stone-900 text-white text-sm hover:bg-stone-800 rounded-sm">
            Register New Case
        </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white border border-stone-200 p-4 rounded-sm grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search case number or title..."
            class="md:col-span-2 px-3 py-2 border border-stone-300 rounded-sm">

        <select name="track" class="px-3 py-2 border border-stone-300 rounded-sm">
            <option value="">All Tracks</option>
            <option value="fast" @selected(request('track') === 'fast')>Fast</option>
            <option value="standard" @selected(request('track') === 'standard')>Standard</option>
            <option value="complex" @selected(request('track') === 'complex')>Complex</option>
        </select>

        <select name="status" class="px-3 py-2 border border-stone-300 rounded-sm">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
            <option value="on_hold" @selected(request('status') === 'on_hold')>On Hold</option>
        </select>

        <button class="px-4 py-2 bg-stone-900 text-white rounded-sm">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-600">
                <tr>
                    <th class="text-left px-4 py-3">Case No.</th>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Judge</th>
                    <th class="text-left px-4 py-3">Track</th>
                    <th class="text-left px-4 py-3">Stage</th>
                    <th class="text-right px-4 py-3">Priority</th>
                    <th class="text-left px-4 py-3">Filed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($cases as $case)
                <tr class="hover:bg-stone-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('cases.show', $case) }}" class="font-mono text-xs text-stone-900 hover:underline">
                            {{ $case->case_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-stone-700">{{ Str::limit($case->title, 35) }}</td>
                    <td class="px-4 py-3 text-stone-600 text-xs">{{ $case->caseType->name }}</td>
                    <td class="px-4 py-3 text-stone-600 text-xs">{{ $case->assignedJudge?->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="track-{{ $case->track }} px-2 py-0.5 text-xs uppercase tracking-wider rounded-sm">
                            {{ $case->track }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-stone-600 capitalize">{{ str_replace('_', ' ', $case->current_stage) }}</td>
                    <td class="px-4 py-3 text-right font-mono">{{ number_format($case->priority_score, 1) }}</td>
                    <td class="px-4 py-3 text-stone-500 text-xs">{{ $case->filing_date->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-stone-500 italic">No cases match these filters</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $cases->links() }}</div>
</div>
@endsection
