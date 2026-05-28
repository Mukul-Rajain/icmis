<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'user_type'         => User::TYPE_LITIGANT,
            'phone'             => fake()->numerify('98#########'),
            'is_active'         => true,
            'is_senior_citizen' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(['user_type' => User::TYPE_ADMIN]);
    }

    public function judge(): static
    {
        return $this->state([
            'user_type'   => User::TYPE_JUDGE,
            'designation' => 'District Judge',
        ]);
    }

    public function lawyer(): static
    {
        return $this->state([
            'user_type'          => User::TYPE_LAWYER,
            'bar_council_number' => 'D/' . fake()->numerify('#####') . '/2015',
            'years_of_practice'  => fake()->numberBetween(2, 30),
        ]);
    }

    public function litigant(): static
    {
        return $this->state(['user_type' => User::TYPE_LITIGANT]);
    }

    public function seniorCitizen(): static
    {
        return $this->state(['is_senior_citizen' => true]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
