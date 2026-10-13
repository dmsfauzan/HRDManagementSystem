<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'created_by' => null,
            'published_at' => now(),
            'is_active' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_active' => false, 'published_at' => null]);
    }
}
