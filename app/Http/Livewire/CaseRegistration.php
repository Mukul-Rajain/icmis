<?php

namespace App\Http\Livewire;

use App\Models\CaseType;
use App\Models\Court;
use App\Services\CaseRegistrationService;
use App\Services\TrackClassifier;
use Livewire\Component;

/**
 * Live case registration form.
 * Shows real-time track preview as the user picks case type and party flags —
 * a great viva demo moment ("watch the system decide the track live").
 */
class CaseRegistration extends Component
{
    // Form fields
    public $title = '';
    public $description = '';
    public $case_type_id = '';
    public $court_id = '';
    public $filing_date;
    public $has_interim_relief_pending = false;

    // Parties (array of arrays)
    public array $parties = [
        ['party_type' => 'petitioner', 'name' => '', 'phone' => '', 'is_senior_citizen' => false, 'is_in_custody' => false],
        ['party_type' => 'respondent', 'name' => '', 'phone' => '', 'is_senior_citizen' => false, 'is_in_custody' => false],
    ];

    // Live track preview
    public ?array $trackPreview = null;

    protected $rules = [
        'title' => 'required|string|max:255',
        'case_type_id' => 'required|exists:case_types,id',
        'court_id' => 'required|exists:courts,id',
        'filing_date' => 'required|date',
        'parties.*.name' => 'required|string|max:255',
        'parties.*.party_type' => 'required|string',
    ];

    public function mount(): void
    {
        $this->filing_date = now()->toDateString();
    }

    public function updated($field): void
    {
        // Recompute the track preview whenever the case type or party flags change
        if (in_array($field, ['case_type_id']) || str_starts_with($field, 'parties.')) {
            $this->updateTrackPreview();
        }
    }

    public function addParty(): void
    {
        $this->parties[] = ['party_type' => 'respondent', 'name' => '', 'phone' => '', 'is_senior_citizen' => false, 'is_in_custody' => false];
    }

    public function removeParty(int $index): void
    {
        if (count($this->parties) > 2) {
            unset($this->parties[$index]);
            $this->parties = array_values($this->parties);
            $this->updateTrackPreview();
        }
    }

    private function updateTrackPreview(): void
    {
        if (! $this->case_type_id) {
            $this->trackPreview = null;
            return;
        }

        $classifier = app(TrackClassifier::class);
        $hasInCustody = collect($this->parties)->contains(fn ($p) => $p['is_in_custody'] ?? false);

        $this->trackPreview = $classifier->explain([
            'case_type_id' => $this->case_type_id,
            'has_in_custody_accused' => $hasInCustody,
            'party_count' => count($this->parties),
        ]);
    }

    public function submit(CaseRegistrationService $registrar)
    {
        $this->validate();

        $case = $registrar->register([
            'title' => $this->title,
            'description' => $this->description,
            'case_type_id' => $this->case_type_id,
            'court_id' => $this->court_id,
            'filing_date' => $this->filing_date,
            'filed_by_user_id' => auth()->id(),
            'has_interim_relief_pending' => $this->has_interim_relief_pending,
            'parties' => $this->parties,
            'lawyers' => [],
        ]);

        session()->flash('success', "Case {$case->case_number} registered on {$case->track} track with priority score {$case->priority_score}");

        return redirect()->route('cases.show', $case);
    }

    public function render()
    {
        return view('livewire.case-registration', [
            'caseTypes' => CaseType::where('is_active', true)->orderBy('name')->get(),
            'courts' => Court::where('is_active', true)->get(),
        ]);
    }
}
