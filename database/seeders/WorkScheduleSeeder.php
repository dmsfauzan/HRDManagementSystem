<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            [
                'code' => 'REG',
                'name' => 'Reguler (Senin - Jumat)',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'work_days' => [1, 2, 3, 4, 5],
                'crosses_midnight' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SHIFT2',
                'name' => 'Shift 2 (Siang)',
                'check_in_time' => '13:00:00',
                'check_out_time' => '22:00:00',
                'late_tolerance_minutes' => 10,
                'work_days' => [1, 2, 3, 4, 5, 6],
                'crosses_midnight' => false,
                'is_active' => true,
            ],
            [
                'code' => 'NIGHT',
                'name' => 'Shift Malam (22:00 - 06:00)',
                'check_in_time' => '22:00:00',
                'check_out_time' => '06:00:00',
                'late_tolerance_minutes' => 10,
                'work_days' => [1, 2, 3, 4, 5, 6, 7],
                'crosses_midnight' => true,
                'is_active' => true,
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::updateOrCreate(['code' => $schedule['code']], $schedule);
        }
    }
}
