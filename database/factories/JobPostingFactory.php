<?php

namespace Database\Factories;

use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('JOB-###')),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'requirements' => fake()->paragraph(),
            'location' => fake()->city(),
            'employment_type' => 'permanent',
            'quota' => 1,
            'status' => JobPosting::STATUS_PUBLISHED,
            'published_at' => now(),
            'show_salary' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => JobPosting::STATUS_DRAFT, 'published_at' => null]);
    }
}
