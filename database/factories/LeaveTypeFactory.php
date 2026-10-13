<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'code' => strtolower(fake()->unique()->lexify('type-???')),
            'name' => fake()->unique()->word().' Leave',
            'is_paid' => true,
            'default_quota' => 12,
            'tracks_balance' => true,
            'carry_max_days' => 2,
            'requires_attachment' => false,
            'is_active' => true,
        ];
    }
}
