<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function checkIn(Employee $employee, ?string $ip = null): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $ip) {
            $now = Carbon::now(tz());
            $baseSchedule = $employee->workSchedule;
            $workDate = $this->resolveWorkDate($now, $baseSchedule);
            $schedule = $employee->scheduleForDate(Carbon::parse($workDate)) ?? $baseSchedule;

            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('work_date', $workDate)
                ->lockForUpdate()
                ->first();

            if ($record && $record->check_in_at) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah melakukan check-in untuk hari kerja ini.',
                ]);
            }

            [$status, $lateMinutes] = $this->evaluateLate($now, $workDate, $schedule);

            $record ??= new AttendanceRecord([
                'employee_id' => $employee->id,
                'work_date' => $workDate,
            ]);

            $record->fill([
                'check_in_at' => $now,
                'check_in_ip' => $ip,
                'status' => $status,
                'late_minutes' => $lateMinutes,
            ])->save();

            return $record;
        });
    }

    public function checkOut(Employee $employee, ?string $ip = null): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $ip) {
            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->orderByDesc('work_date')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'attendance' => 'Tidak ada sesi check-in aktif. Lakukan check-in terlebih dahulu.',
                ]);
            }

            $now = Carbon::now(tz());

            if ($now->lessThanOrEqualTo($record->check_in_at)) {
                throw ValidationException::withMessages([
                    'attendance' => 'Waktu check-out tidak valid.',
                ]);
            }

            $record->fill([
                'check_out_at' => $now,
                'check_out_ip' => $ip,
                'work_minutes' => $record->check_in_at->diffInMinutes($now),
            ])->save();

            return $record;
        });
    }

    public function applyCorrection(AttendanceRecord $record, AttendanceCorrection $correction): AttendanceRecord
    {
        return DB::transaction(function () use ($record, $correction) {
            $checkIn = $correction->requested_check_in_at ?? $record->check_in_at;
            $checkOut = $correction->requested_check_out_at ?? $record->check_out_at;

            $workDate = $correction->work_date->toDateString();
            $schedule = $record->employee?->scheduleForDate($correction->work_date) ?? $record->employee?->workSchedule;

            $status = $record->status;
            $lateMinutes = $record->late_minutes;

            if ($checkIn && $schedule && in_array($status, ['present', 'late', 'absent'], true)) {
                [$status, $lateMinutes] = $this->evaluateLate(
                    Carbon::parse($checkIn)->timezone(tz()),
                    $workDate,
                    $schedule
                );
            }

            $record->fill([
                'check_in_at' => $checkIn,
                'check_out_at' => $checkOut,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'work_minutes' => ($checkIn && $checkOut)
                    ? Carbon::parse($checkIn)->diffInMinutes(Carbon::parse($checkOut))
                    : $record->work_minutes,
                'notes' => trim(($record->notes ?? '').' [Koreksi disetujui]'),
            ])->save();

            return $record;
        });
    }

    /**
     * @return array{0: string, 1: int}
     */
    public function evaluateLate(Carbon $checkIn, mixed $workDate, ?WorkSchedule $schedule): array
    {
        if (! $schedule) {
            return ['present', 0];
        }

        $expected = Carbon::parse(Carbon::parse($workDate)->toDateString().' '.$schedule->check_in_time, tz());
        $minutesLate = $expected->diffInMinutes($checkIn, false);

        if ($minutesLate > $schedule->late_tolerance_minutes) {
            return ['late', max(0, (int) $minutesLate)];
        }

        return ['present', 0];
    }

    public function resolveWorkDate(Carbon $now, ?WorkSchedule $schedule): string
    {
        if ($schedule && $schedule->crosses_midnight) {
            $checkInTime = Carbon::parse($now->toDateString().' '.$schedule->check_in_time, tz());
            if ($now->lessThan($checkInTime)) {
                return $now->copy()->subDay()->toDateString();
            }
        }

        return $now->toDateString();
    }

    public function isScheduledWorkDay(Employee $employee, Carbon $date): bool
    {
        $schedule = $employee->scheduleForDate($date);

        if (! $schedule) {
            return $date->isWeekday();
        }

        return $schedule->isWorkDay((int) $date->dayOfWeekIso);
    }
}
