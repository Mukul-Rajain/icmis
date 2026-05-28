<?php

namespace Database\Factories;

use App\Models\CaseType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseTypeFactory extends Factory
{
    protected $model = CaseType::class;

    public function definition(): array
    {
        return [
            'name'                    => fake()->words(3, true),
            'code'                    => strtoupper(fake()->lexify('???')),
            'category'                => fake()->randomElement(['criminal', 'civil', 'family', 'commercial', 'constitutional']),
            'default_track'           => fake()->randomElement(['fast', 'standard', 'complex']),
            'base_priority'           => fake()->randomFloat(2, 10, 30),
            'typical_duration_days'   => fake()->numberBetween(60, 730),
            'is_time_sensitive'       => fake()->boolean(30),
            'is_active'               => true,
        ];
    }

    public function fast(): static
    {
        return $this->state([
            'default_track'     => 'fast',
            'is_time_sensitive' => true,
            'base_priority'     => 25.0,
        ]);
    }

    public function complex(): static
    {
        return $this->state([
            'default_track' => 'complex',
            'base_priority' => 12.0,
        ]);
    }
}
