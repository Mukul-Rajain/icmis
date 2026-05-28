<?php

namespace Database\Factories;

use App\Models\CourtCase;
use App\Models\Hearing;
use Illuminate\Database\Eloquent\Factories\Factory;

class HearingFactory extends Factory
{
    protected $model = Hearing::class;

    public function definition(): array
    {
        $case = CourtCase::inRandomOrder()->first()
            ?? CourtCase::factory()->create();

        return [
            'case_id'           => $case->id,
            'judge_id'          => $case->assigned_judge_id,
            'court_id'          => $case->court_id,
            'scheduled_date'    => fake()->dateTimeBetween('-1 year', '+3 months'),
            'scheduled_time'    => fake()->randomElement(['10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00']),
            'courtroom_number'  => 'Court ' . fake()->numberBetween(1, 5),
            'status'            => Hearing::STATUS_SCHEDULED,
            'stage_at_hearing'  => $case->current_stage,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status'        => Hearing::STATUS_COMPLETED,
            'outcome'       => fake()->sentences(2, true),
            'scheduled_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function adjourned(): static
    {
        return $this->state([
            'status'        => Hearing::STATUS_ADJOURNED,
            'scheduled_date' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'status'        => Hearing::STATUS_SCHEDULED,
            'scheduled_date' => fake()->dateTimeBetween('+1 day', '+3 months'),
        ]);
    }
}
