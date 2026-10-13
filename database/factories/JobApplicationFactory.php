<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'candidate_name' => fake()->name(),
            'candidate_email' => fake()->unique()->safeEmail(),
            'candidate_phone' => '08'.fake()->numerify('##########'),
            'domicile' => fake()->city(),
            'education' => 'S1',
            'experience_years' => fake()->numberBetween(0, 8),
            'source' => 'career_page',
            'cover_letter' => fake()->paragraph(),
            'cv_path' => 'recruitment/0/cv.pdf',
            'status' => 'applied',
        ];
    }
}
