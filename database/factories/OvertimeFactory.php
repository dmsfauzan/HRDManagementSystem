<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Database\Eloquent\Factories\Factory;

class OvertimeFactory extends Factory
{
    protected $model = Overtime::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => now()->subDay()->toDateString(),
            'start_at' => '17:00:00',
            'end_at' => '19:00:00',
            'duration_minutes' => 120,
            'reason' => fake()->sentence(),
            'status' => Overtime::STATUS_PENDING,
        ];
    }
}
