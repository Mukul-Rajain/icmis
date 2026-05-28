<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourtFactory extends Factory
{
    protected $model = Court::class;

    public function definition(): array
    {
        $cities = ['Delhi', 'Mumbai', 'Pune', 'Chennai', 'Kolkata', 'Hyderabad', 'Ahmedabad', 'Jaipur'];
        $city   = fake()->randomElement($cities);

        return [
            'name'             => "District Court, {$city}",
            'court_code'       => 'DC-' . strtoupper(substr($city, 0, 3)) . '-' . fake()->numberBetween(1, 9),
            'court_type'       => fake()->randomElement(['district', 'high', 'sessions', 'family', 'consumer']),
            'location'         => fake()->streetAddress() . ', ' . $city,
            'jurisdiction'     => $city . ' District',
            'total_courtrooms' => fake()->numberBetween(3, 20),
            'is_active'        => true,
        ];
    }
}
