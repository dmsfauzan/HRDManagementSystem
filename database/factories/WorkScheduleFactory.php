<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('SCH-??')),
            'name' => 'Jadwal '.fake()->word(),
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5],
            'crosses_midnight' => false,
            'is_active' => true,
        ];
    }
}
