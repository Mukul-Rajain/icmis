<?php

namespace Database\Factories;

use App\Models\CaseType;
use App\Models\Court;
use App\Models\CourtCase;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourtCaseFactory extends Factory
{
    protected $model = CourtCase::class;

    // Incrementing serial for case numbers
    private static int $serial = 1000;

    public function definition(): array
    {
        $track       = fake()->randomElement(['fast', 'standard', 'complex']);
        $filingDate  = fake()->dateTimeBetween('-3 years', '-1 month');
        $daysInTrack = ['fast' => 90, 'standard' => 365, 'complex' => 730];

        $caseType = CaseType::inRandomOrder()->first()
            ?? CaseType::factory()->create();

        $court = Court::inRandomOrder()->first()
            ?? Court::factory()->create();

        $judge = Judge::inRandomOrder()->first();

        $filer = User::litigant()->inRandomOrder()->first()
            ?? User::factory()->litigant()->create();

        $year   = date('Y', strtotime($filingDate->format('Y-m-d')));
        $serial = ++self::$serial;

        return [
            'case_number'               => "CASE/{$year}/{$serial}",
            'title'                     => $this->generateTitle($caseType),
            'description'               => fake()->sentences(3, true),
            'case_type_id'              => $caseType->id,
            'court_id'                  => $court->id,
            'assigned_judge_id'         => $judge?->id,
            'filed_by_user_id'          => $filer->id,
            'filing_date'               => $filingDate,
            'status'                    => CourtCase::STATUS_ACTIVE,
            'track'                     => $track,
            'priority_score'            => fake()->randomFloat(2, 20, 100),
            'current_stage'             => fake()->randomElement([
                'registered', 'notice_issued', 'reply_filed',
                'evidence', 'arguments', 'judgment_reserved',
            ]),
            'expected_disposal_date'    => fake()->dateTimeBetween('now', '+2 years'),
            'hearing_count'             => fake()->numberBetween(0, 20),
            'adjournment_count'         => fake()->numberBetween(0, 10),
            'has_interim_relief_pending' => fake()->boolean(20),
            'has_in_custody_accused'    => fake()->boolean(15),
            'involves_senior_citizen'   => fake()->boolean(10),
        ];
    }

    public function fastTrack(): static
    {
        return $this->state([
            'track'          => 'fast',
            'priority_score' => fake()->randomFloat(2, 70, 100),
        ]);
    }

    public function standard(): static
    {
        return $this->state([
            'track'          => 'standard',
            'priority_score' => fake()->randomFloat(2, 40, 75),
        ]);
    }

    public function complex(): static
    {
        return $this->state([
            'track'          => 'complex',
            'priority_score' => fake()->randomFloat(2, 20, 60),
        ]);
    }

    public function disposed(): static
    {
        return $this->state([
            'status'     => CourtCase::STATUS_DISPOSED,
            'disposed_on' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function atRisk(): static
    {
        return $this->state([
            'status'                 => CourtCase::STATUS_ACTIVE,
            'track'                  => 'standard',
            'priority_score'         => fake()->randomFloat(2, 80, 98),
            'adjournment_count'      => fake()->numberBetween(5, 12),
            'expected_disposal_date' => fake()->dateTimeBetween('-30 days', '+30 days'),
        ]);
    }

    private function generateTitle(CaseType $caseType): string
    {
        $indianNames = [
            'Rajesh Kumar', 'Priya Sharma', 'Sunita Devi', 'Mohan Lal', 'Kavita Singh',
            'Vikram Gupta', 'Anita Verma', 'Suresh Yadav', 'Meena Kumari', 'Arun Mishra',
        ];

        $name1 = fake()->randomElement($indianNames);
        $name2 = fake()->randomElement($indianNames);
        $type  = $caseType->name ?? 'Petition';

        return "{$type} – {$name1} vs {$name2}";
    }
}
