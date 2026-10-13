<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyProfileFactory extends Factory
{
    protected $model = CompanyProfile::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tagline' => fake()->catchPhrase(),
            'about' => fake()->paragraph(),
            'city' => fake()->city(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
        ];
    }
}
