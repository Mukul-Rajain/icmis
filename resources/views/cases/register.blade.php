@extends('layouts.app')
@section('title', 'Register New Case')

@section('content')
<div class="space-y-6">
    <div class="border-b border-stone-300 pb-4">
        <p class="text-xs uppercase tracking-widest text-stone-500 mb-2">New Filing</p>
        <h1 class="font-display text-4xl font-semibold text-stone-900">Register a Case</h1>
        <p class="text-sm text-stone-600 mt-1">The system will auto-classify the track and compute the initial priority score</p>
    </div>

    @livewire('case-registration')
</div>
@endsection
