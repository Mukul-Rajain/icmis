@extends('layouts.app')

@section('title', 'Record Outcome – Hearing')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-8">
    <a href="{{ route('hearings.index') }}" class="text-xs uppercase tracking-wider text-stone-400 hover:text-stone-600">
        ← Back to Hearings
    </a>

    <div class="mt-4 mb-6">
        <h1 class="font-display text-3xl font-semibold text-stone-900">Record Hearing Outcome</h1>
        <p class="text-stone-500 mt-1 font-mono text-sm">
            {{ $hearing->case->case_number }} · {{ $hearing->scheduled_date->format('d M Y') }}
            @if($hearing->scheduled_time) at {{ $hearing->scheduled_time }} @endif
        </p>
        <p class="text-stone-700 mt-1">{{ $hearing->case->title }}</p>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 text-sm p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white border border-stone-200 p-6">
        <div class="border-t-2 border-double border-stone-300 pt-6">

            <form method="POST" action="{{ route('hearings.outcome.store', $hearing) }}">
                @csrf

                <div class="mb-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">
                        Outcome / Proceedings <span class="text-red-500">*</span>
                    </label>
                    <textarea name="outcome" rows="4" required
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500"
                        placeholder="Describe what happened at today's hearing…">{{ old('outcome') }}</textarea>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">
                        Update Case Stage
                    </label>
                    <select name="new_stage"
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500">
                        <option value="">— No change —</option>
                        <option value="notice_issued">Notice Issued</option>
                        <option value="reply_filed">Reply Filed</option>
                        <option value="evidence">Evidence</option>
                        <option value="arguments">Arguments</option>
                        <option value="judgment_reserved">Judgment Reserved</option>
                        <option value="disposed">Disposed</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">
                        Next Hearing Date
                    </label>
                    <input type="date" name="next_hearing_date" value="{{ old('next_hearing_date') }}"
                        min="{{ now()->addDay()->toDateString() }}"
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500">
                    <p class="text-xs text-stone-400 mt-1">Leave blank if no hearing is being scheduled now.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">
                        Next Action
                    </label>
                    <input type="text" name="next_action" value="{{ old('next_action') }}"
                        placeholder="e.g. Cross-examination of PW-3"
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500">
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-stone-900 text-white px-6 py-2.5 text-sm font-medium hover:bg-stone-800">
                        Save Outcome
                    </button>
                    <a href="{{ route('hearings.index') }}" class="px-6 py-2.5 text-sm text-stone-600 border border-stone-300 hover:bg-stone-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
