@extends('layouts.app')
@section('title', 'Generate Cause List')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="border-b border-stone-300 pb-4">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Cause List Generator</p>
        <h1 class="font-display text-4xl font-semibold text-stone-900">Generate New List</h1>
        <p class="text-sm text-stone-600 mt-1">The system will pick cases based on track precedence and priority score</p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-700 px-4 py-3 text-sm text-red-900">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('cause-lists.generate') }}" class="bg-white border border-stone-200 p-6 rounded-sm space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                Select Judge
            </label>
            <select name="judge_id" required class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm">
                <option value="">-- Choose Judge --</option>
                @foreach($judges as $judge)
                    <option value="{{ $judge->id }}">
                        {{ $judge->user->name }} — {{ $judge->court->name }} (max {{ $judge->max_daily_cases }} cases/day)
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                For Date
            </label>
            <input type="date" name="list_date" value="{{ now()->addDay()->toDateString() }}" min="{{ now()->toDateString() }}" required
                class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm">
        </div>

        <div class="bg-stone-50 p-4 text-xs text-stone-600 border-l-2 border-stone-300">
            <p class="font-semibold text-stone-800 mb-1">What happens when you generate?</p>
            <ul class="space-y-1 list-disc list-inside">
                <li>Cases with hearings already scheduled for this date are included automatically</li>
                <li>Remaining slots are filled with high-priority cases needing listing</li>
                <li>Ordered by track (fast → standard → complex), then priority score</li>
                <li>Conflicts (lawyers in two courts same day) are flagged for review</li>
                <li>You can review the draft list before publishing</li>
            </ul>
        </div>

        <button type="submit" class="px-6 py-2.5 bg-stone-900 text-white text-sm font-medium rounded-sm hover:bg-stone-800">
            Generate List
        </button>
    </form>
</div>
@endsection
