<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where('employee_id', 'IT-002')->first();
        $manager = Employee::where('employee_id', 'IT-001')->first();

        if (! $employee) {
            return;
        }

        $tz = tz();

        foreach (range(1, 7) as $daysAgo) {
            $date = Carbon::today($tz)->subDays($daysAgo);
            if ($date->isWeekend()) {
                continue;
            }

            $status = $daysAgo % 4 === 0 ? 'late' : 'present';
            $checkIn = Carbon::parse($date->format('Y-m-d').' 08:00:00', $tz)->addMinutes($status === 'late' ? 25 : -3);

            AttendanceRecord::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $date->toDateString()],
                [
                    'check_in_at' => $checkIn,
                    'check_out_at' => Carbon::parse($date->format('Y-m-d').' 17:05:00', $tz),
                    'status' => $status,
                    'late_minutes' => $status === 'late' ? 10 : 0,
                    'work_minutes' => 540,
                    'check_in_ip' => '127.0.0.1',
                ]
            );
        }

        $annual = LeaveType::where('code', 'annual')->first();

        if ($annual && $manager) {
            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'start_date' => Carbon::today($tz)->addDays(10)->toDateString(),
                ],
                [
                    'leave_type_id' => $annual->id,
                    'end_date' => Carbon::today($tz)->addDays(12)->toDateString(),
                    'total_days' => 3,
                    'reason' => 'Keperluan keluarga di luar kota.',
                    'status' => LeaveRequest::STATUS_PENDING,
                    'requested_at' => now(),
                ]
            );
        }
    }
}
