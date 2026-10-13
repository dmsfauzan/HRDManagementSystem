<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $date = now()->subDay();

        return [
            'employee_id' => Employee::factory(),
            'work_date' => $date->toDateString(),
            'check_in_at' => $date->copy()->setTime(8, 0),
            'check_out_at' => $date->copy()->setTime(17, 0),
            'status' => 'present',
            'late_minutes' => 0,
            'work_minutes' => 540,
            'check_in_ip' => '127.0.0.1',
        ];
    }
}
