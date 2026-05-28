<?php

namespace Tests\Unit;

use App\Models\CaseType;
use App\Models\CourtCase;
use App\Services\PriorityScorer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityScorerTest extends TestCase
{
    use RefreshDatabase;

    private PriorityScorer $scorer;
    private CaseType $bailType;
    private CaseType $civilType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new PriorityScorer();

        $this->bailType = CaseType::factory()->create([
            'code' => 'BAIL', 'base_priority' => 90,
            'default_track' => 'fast', 'is_time_sensitive' => true,
            'typical_duration_days' => 30,
        ]);

        $this->civilType = CaseType::factory()->create([
            'code' => 'CIV', 'base_priority' => 50,
            'default_track' => 'standard', 'is_time_sensitive' => false,
            'typical_duration_days' => 365,
        ]);
    }

    public function test_bail_case_scores_higher_than_civil_case_of_same_age(): void
    {
        $bail = CourtCase::factory()->create([
            'case_type_id' => $this->bailType->id,
            'track' => 'fast',
            'filing_date' => Carbon::today()->subDays(10),
            'current_stage' => 'registered',
        ]);

        $civil = CourtCase::factory()->create([
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(10),
            'current_stage' => 'registered',
        ]);

        $bailScore = $this->scorer->score($bail)['score'];
        $civilScore = $this->scorer->score($civil)['score'];

        $this->assertGreaterThan($civilScore, $bailScore,
            'Bail (time-sensitive) should score higher than civil case');
    }

    public function test_older_case_scores_higher_than_newer_case_of_same_type(): void
    {
        $old = CourtCase::factory()->create([
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(300),
        ]);
        $new = CourtCase::factory()->create([
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(10),
        ]);

        $this->assertGreaterThan(
            $this->scorer->score($new)['score'],
            $this->scorer->score($old)['score']
        );
    }

    public function test_case_with_many_adjournments_scores_higher(): void
    {
        $base = [
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(100),
        ];

        $stalled = CourtCase::factory()->create(array_merge($base, ['adjournment_count' => 8]));
        $clean = CourtCase::factory()->create(array_merge($base, ['adjournment_count' => 0]));

        $this->assertGreaterThan(
            $this->scorer->score($clean)['score'],
            $this->scorer->score($stalled)['score']
        );
    }

    public function test_case_with_in_custody_accused_gets_stakeholder_boost(): void
    {
        $base = [
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(50),
        ];

        $withCustody = CourtCase::factory()->create(array_merge($base, ['has_in_custody_accused' => true]));
        $without = CourtCase::factory()->create(array_merge($base, ['has_in_custody_accused' => false]));

        $this->assertGreaterThan(
            $this->scorer->score($without)['score'],
            $this->scorer->score($withCustody)['score']
        );
    }

    public function test_score_is_always_within_0_to_100(): void
    {
        // Extreme case: very old + many adjournments + every flag set
        $extreme = CourtCase::factory()->create([
            'case_type_id' => $this->bailType->id,
            'track' => 'fast',
            'filing_date' => Carbon::today()->subYears(5),
            'adjournment_count' => 20,
            'has_in_custody_accused' => true,
            'has_interim_relief_pending' => true,
            'involves_senior_citizen' => true,
            'current_stage' => 'judgment_reserved',
        ]);

        $score = $this->scorer->score($extreme)['score'];

        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_factors_breakdown_is_returned(): void
    {
        $case = CourtCase::factory()->create([
            'case_type_id' => $this->civilType->id,
            'track' => 'standard',
            'filing_date' => Carbon::today()->subDays(100),
        ]);

        $result = $this->scorer->score($case);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertArrayHasKey('age_contribution', $result['factors']);
        $this->assertArrayHasKey('adjournment_contribution', $result['factors']);
        $this->assertArrayHasKey('stage_contribution', $result['factors']);
    }
}
