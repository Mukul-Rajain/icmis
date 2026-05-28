<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- ─── FORM ─── --}}
    <div class="lg:col-span-2">
        <div class="bg-white border border-stone-200 rounded-sm shadow-sm">
            <div class="double-rule px-6 pt-6">
                <h2 class="font-display text-2xl font-semibold text-stone-900">Case Registration</h2>
                <p class="text-sm text-stone-500 mt-1">All fields marked with <span class="text-red-700">*</span> are mandatory</p>
            </div>

            <form wire:submit.prevent="submit" class="p-6 space-y-6">

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                        Case Title <span class="text-red-700">*</span>
                    </label>
                    <input type="text" wire:model="title"
                        class="w-full px-3 py-2 border border-stone-300 rounded-sm focus:border-stone-800 focus:ring-1 focus:ring-stone-800 text-sm">
                    @error('title') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                            Case Type <span class="text-red-700">*</span>
                        </label>
                        <select wire:model.live="case_type_id"
                            class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm">
                            <option value="">-- Select --</option>
                            @foreach($caseTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                            Court <span class="text-red-700">*</span>
                        </label>
                        <select wire:model="court_id" class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm">
                            <option value="">-- Select --</option>
                            @foreach($courts as $court)
                                <option value="{{ $court->id }}">{{ $court->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 mb-1.5">
                        Description
                    </label>
                    <textarea wire:model="description" rows="3"
                        class="w-full px-3 py-2 border border-stone-300 rounded-sm text-sm"></textarea>
                </div>

                <div class="flex items-center gap-3 p-3 bg-amber-50 border-l-4 border-amber-600">
                    <input type="checkbox" wire:model.live="has_interim_relief_pending" id="interim" class="w-4 h-4">
                    <label for="interim" class="text-sm text-stone-700">
                        <span class="font-medium">Interim relief pending</span> — boosts priority score
                    </label>
                </div>

                {{-- ─── Parties ─── --}}
                <div class="border-t border-stone-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg font-semibold text-stone-900">Parties</h3>
                        <button type="button" wire:click="addParty"
                            class="text-xs uppercase tracking-wider text-stone-700 hover:text-stone-900 underline">
                            + Add Party
                        </button>
                    </div>

                    @foreach($parties as $i => $party)
                    <div class="grid grid-cols-12 gap-3 mb-3 items-center bg-stone-50 p-3 rounded-sm">
                        <select wire:model.live="parties.{{ $i }}.party_type" class="col-span-3 px-2 py-1.5 border border-stone-300 text-sm rounded-sm">
                            <option value="petitioner">Petitioner</option>
                            <option value="respondent">Respondent</option>
                            <option value="plaintiff">Plaintiff</option>
                            <option value="defendant">Defendant</option>
                            <option value="accused">Accused</option>
                        </select>

                        <input type="text" wire:model="parties.{{ $i }}.name" placeholder="Name"
                            class="col-span-4 px-2 py-1.5 border border-stone-300 text-sm rounded-sm">

                        <label class="col-span-2 flex items-center gap-1.5 text-xs">
                            <input type="checkbox" wire:model.live="parties.{{ $i }}.is_in_custody">
                            In custody
                        </label>

                        <label class="col-span-2 flex items-center gap-1.5 text-xs">
                            <input type="checkbox" wire:model.live="parties.{{ $i }}.is_senior_citizen">
                            Senior citizen
                        </label>

                        <button type="button" wire:click="removeParty({{ $i }})"
                            class="col-span-1 text-red-700 text-xs hover:underline">×</button>
                    </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-stone-200">
                    <button type="submit" class="px-6 py-2.5 bg-stone-900 text-white text-sm font-medium tracking-wide hover:bg-stone-800 rounded-sm">
                        Register Case
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── LIVE TRACK PREVIEW ─── --}}
    <div class="lg:col-span-1">
        <div class="sticky top-6">
            <div class="bg-stone-900 text-stone-100 p-6 rounded-sm">
                <p class="text-xs uppercase tracking-widest text-stone-400 mb-3">DCFM Live Preview</p>

                @if($trackPreview)
                    <div class="mb-4">
                        <p class="text-xs text-stone-400 mb-2">Assigned Track</p>
                        <div class="font-display text-3xl font-semibold capitalize">
                            {{ $trackPreview['track'] }}
                            <span class="text-stone-500">·</span>
                            <span class="text-amber-300 text-sm uppercase tracking-wider">
                                @if($trackPreview['track'] === 'fast') Priority listing
                                @elseif($trackPreview['track'] === 'standard') Normal flow
                                @else Detailed handling
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-stone-700 pt-4">
                        <p class="text-xs text-stone-400 mb-2">Classification Reasoning</p>
                        <ul class="space-y-2 text-sm text-stone-200">
                            @foreach($trackPreview['reasons'] as $reason)
                                <li class="flex gap-2">
                                    <span class="text-amber-400">→</span>
                                    <span>{{ $reason }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-sm text-stone-400 italic">Select a case type to see how DCFM will route this case…</p>
                @endif
            </div>

            <div class="mt-4 p-4 bg-stone-50 border border-stone-200 text-xs text-stone-600">
                <p class="font-semibold text-stone-800 mb-1">How does this work?</p>
                <p>The system evaluates the case type, parties, and flags through a rule-based classifier. Priority score will be computed after registration based on age, urgency, stage, and adjournment history.</p>
            </div>
        </div>
    </div>
</div>
