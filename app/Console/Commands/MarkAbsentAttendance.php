<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAttendance extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : Tanggal yang diproses (YYYY-MM-DD), default hari ini}';

    protected $description = 'Membuat catatan absensi Tidak Hadir/Cuti untuk hari kerja tanpa check-in.';

    public function handle(AttendanceService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'), AttendanceService::TZ)->startOfDay()
            : Carbon::today(AttendanceService::TZ);

        if (Holiday::whereDate('date', $date->toDateString())->exists()) {
            $this->info("{$date->toDateString()} adalah hari libur. Tidak ada yang diproses.");

            return self::SUCCESS;
        }

        $created = 0;
        $employees = Employee::where('employment_status', EmploymentStatus::Active->value)
            ->with('workSchedule')
            ->get();

        foreach ($employees as $employee) {
            if (! $service->isScheduledWorkDay($employee, $date)) {
                continue;
            }

            $exists = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('work_date', $date->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            $leave = LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date->toDateString())
                ->whereDate('end_date', '>=', $date->toDateString())
                ->first();

            $status = $leave
                ? ($leave->leaveType?->code === 'permit' ? AttendanceStatus::Permit->value : AttendanceStatus::Leave->value)
                : AttendanceStatus::Absent->value;

            AttendanceRecord::create([
                'employee_id' => $employee->id,
                'work_date' => $date->toDateString(),
                'status' => $status,
                'notes' => $leave ? 'Dibuat otomatis dari pengajuan cuti disetujui.' : 'Dibuat otomatis (tidak ada check-in).',
            ]);

            $created++;
        }

        $this->info("Selesai. {$created} catatan absensi dibuat untuk {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
