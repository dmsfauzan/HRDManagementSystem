<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoRosterSeeder extends Seeder
{
    public function run(): void
    {
        $tz = tz();
        $today = Carbon::today($tz);

        $employees = Employee::pluck('id')->all();
        $schedules = WorkSchedule::pluck('id')->all();
        $departments = Department::pluck('id')->all();
        $positions = Position::pluck('id')->all();

        if (empty($schedules) || empty($departments) || empty($positions)) {
            return;
        }

        $names = [
            'Ayu Lestari', 'Rudi Hartono', 'Nina Kurnia', 'Dedi Prasetyo', 'Maya Anggraini',
            'Joko Susilo', 'Fitri Handayani', 'Agus Setiawan', 'Rina Wulandari', 'Hendra Gunawan',
            'Dewi Sartika', 'Fajar Nugroho', 'Intan Permata', 'Bambang Irawan', 'Sari Melati',
        ];

        $seq = 10;
        foreach ($names as $i => $name) {
            $seq++;
            $prefix = $departments[$i % count($departments)] === $departments[0] ? 'IT' : 'OPS';

            $employee = Employee::firstOrCreate(
                ['employee_id' => sprintf('%s-%03d', $prefix, $seq)],
                [
                    'user_id' => null,
                    'full_name' => $name,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@hris.test',
                    'phone' => '0812'.random_int(1000000, 9999999),
                    'department_id' => $departments[$i % count($departments)],
                    'position_id' => $positions[$i % count($positions)],
                    'manager_id' => Employee::where('employee_id', 'IT-001')->value('id'),
                    'work_schedule_id' => $schedules[$i % count($schedules)],
                    'join_date' => $today->copy()->subMonths(random_int(2, 36))->toDateString(),
                    'employment_type' => ['permanent', 'permanent', 'contract', 'probation'][random_int(0, 3)],
                    'employment_status' => 'active',
                ]
            );
            $employees[] = $employee->id;
        }

        $annual = LeaveType::where('code', 'annual')->first();

        foreach ($employees as $employeeId) {
            if ($annual) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employeeId, 'leave_type_id' => $annual->id, 'year' => (int) $today->format('Y')],
                    ['balance' => $annual->default_quota, 'used' => 0]
                );
            }

            foreach (range(1, 30) as $daysAgo) {
                $date = $today->copy()->subDays($daysAgo);
                if ($date->isWeekend()) {
                    continue;
                }

                if (AttendanceRecord::where('employee_id', $employeeId)->whereDate('work_date', $date->toDateString())->exists()) {
                    continue;
                }

                $roll = $employeeId % 10;
                $status = match (true) {
                    $roll >= 8 && $daysAgo % 5 === 0 => AttendanceStatus::Late->value,
                    $roll >= 9 && $daysAgo % 7 === 0 => AttendanceStatus::Absent->value,
                    default => AttendanceStatus::Present->value,
                };

                AttendanceRecord::create([
                    'employee_id' => $employeeId,
                    'work_date' => $date->toDateString(),
                    'check_in_at' => $status === AttendanceStatus::Absent->value ? null : Carbon::parse($date->format('Y-m-d').' 08:00:00', $tz)->addMinutes($status === AttendanceStatus::Late->value ? 28 : -4),
                    'check_out_at' => $status === AttendanceStatus::Absent->value ? null : Carbon::parse($date->format('Y-m-d').' 17:04:00', $tz),
                    'status' => $status,
                    'late_minutes' => $status === AttendanceStatus::Late->value ? 13 : 0,
                    'work_minutes' => $status === AttendanceStatus::Absent->value ? 0 : 545,
                    'check_in_ip' => '127.0.0.1',
                ]);
            }
        }

        if ($annual) {
            $sample = Employee::whereIn('id', $employees)->take(3)->get();
            foreach ($sample as $idx => $employee) {
                $start = $today->copy()->addDays(15 + $idx * 5);
                $end = $start->copy()->addDays(2);
                LeaveRequest::firstOrCreate(
                    ['employee_id' => $employee->id, 'start_date' => $start->toDateString()],
                    [
                        'leave_type_id' => $annual->id,
                        'end_date' => $end->toDateString(),
                        'total_days' => 3,
                        'reason' => 'Liburan keluarga.',
                        'status' => LeaveRequest::STATUS_PENDING,
                        'requested_at' => now(),
                    ]
                );
            }
        }

        Announcement::firstOrCreate(
            ['title' => 'Selamat Datang di HRIS'],
            [
                'body' => "Halo semuanya,\n\nAplikasi HRIS kini aktif. Silakan lakukan check-in/check-out setiap hari kerja, ajukan cuti melalui menu Cuti, dan pantau pengumuman di halaman ini.\n\nTerima kasih.",
                'created_by' => null,
                'published_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
