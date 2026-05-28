@extends('layouts.app')
@section('title', 'Check Case Status')

@section('content')
<div class="max-w-md mx-auto py-12 space-y-6">
    <div class="text-center">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">Public Services</p>
        <h1 class="font-display text-3xl font-semibold text-stone-900">Check Case Status</h1>
        <p class="text-sm text-stone-600 mt-2">Look up case status with your case number and party name</p>
    </div>

    <form method="POST" action="{{ route('public.case-status.lookup') }}" class="bg-white border border-stone-200 p-6 rounded-sm space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                Case Number
            </label>
            <input type="text" name="case_number" value="{{ old('case_number') }}" required placeholder="e.g., CASE/2026/00123"
                class="w-full px-3 py-2 border border-stone-300 rounded-sm font-mono text-sm">
            @error('case_number') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                Petitioner / Respondent Name
            </label>
            <input type="text" name="verification_name" value="{{ old('verification_name') }}" required
                class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm">
            <p class="text-xs text-stone-500 mt-1">Enter the name of any party in the case for verification</p>
        </div>

        <button type="submit" class="w-full px-4 py-2.5 bg-stone-900 text-white text-sm font-medium rounded-sm hover:bg-stone-800">
            Look Up Case
        </button>
    </form>
</div>
@endsection
