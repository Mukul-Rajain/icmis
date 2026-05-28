<?php

namespace Tests\Unit;

use App\Models\CaseType;
use App\Services\TrackClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackClassifierTest extends TestCase
{
    use RefreshDatabase;

    private TrackClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new TrackClassifier();
    }

    public function test_time_sensitive_case_goes_to_fast_track(): void
    {
        $type = CaseType::factory()->create([
            'is_time_sensitive' => true,
            'default_track' => 'standard', // even if default is standard...
            'category' => 'criminal',
        ]);

        $track = $this->classifier->classify(['case_type_id' => $type->id]);
        $this->assertEquals('fast', $track);
    }

    public function test_in_custody_accused_triggers_fast_track(): void
    {
        $type = CaseType::factory()->create([
            'is_time_sensitive' => false,
            'default_track' => 'standard',
            'category' => 'criminal',
        ]);

        $track = $this->classifier->classify([
            'case_type_id' => $type->id,
            'has_in_custody_accused' => true,
        ]);
        $this->assertEquals('fast', $track);
    }

    public function test_constitutional_case_goes_complex(): void
    {
        $type = CaseType::factory()->create([
            'is_time_sensitive' => false,
            'default_track' => 'standard',
            'category' => 'constitutional',
        ]);

        $track = $this->classifier->classify(['case_type_id' => $type->id]);
        $this->assertEquals('complex', $track);
    }

    public function test_multi_party_commercial_goes_complex(): void
    {
        $type = CaseType::factory()->create([
            'is_time_sensitive' => false,
            'default_track' => 'standard',
            'category' => 'commercial',
        ]);

        $track = $this->classifier->classify([
            'case_type_id' => $type->id,
            'party_count' => 6,
        ]);
        $this->assertEquals('complex', $track);
    }

    public function test_falls_back_to_default_track(): void
    {
        $type = CaseType::factory()->create([
            'is_time_sensitive' => false,
            'default_track' => 'standard',
            'category' => 'civil',
        ]);

        $track = $this->classifier->classify(['case_type_id' => $type->id]);
        $this->assertEquals('standard', $track);
    }

    public function test_explain_returns_reasoning(): void
    {
        $type = CaseType::factory()->create([
            'name' => 'Bail Application',
            'is_time_sensitive' => true,
            'default_track' => 'fast',
            'category' => 'criminal',
        ]);

        $explanation = $this->classifier->explain(['case_type_id' => $type->id]);

        $this->assertEquals('fast', $explanation['track']);
        $this->assertNotEmpty($explanation['reasons']);
        $this->assertStringContainsString('time-sensitive', $explanation['reasons'][0]);
    }
}
