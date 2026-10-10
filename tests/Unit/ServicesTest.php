<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_late_when_checkin_exceeds_tolerance(): void
    {
        $service = app(AttendanceService::class);
        $schedule = WorkSchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5],
        ]);

        $workDate = '2026-10-12';
        [$status, $late] = $service->evaluateLate(
            Carbon::parse('2026-10-12 08:16', 'Asia/Jakarta'),
            $workDate,
            $schedule
        );

        $this->assertSame('late', $status);
        $this->assertGreaterThan(15, $late);
    }

    public function test_attendance_on_time_is_present(): void
    {
        $service = app(AttendanceService::class);
        $schedule = WorkSchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5],
        ]);

        [$status, $late] = $service->evaluateLate(
            Carbon::parse('2026-10-12 08:10', 'Asia/Jakarta'),
            '2026-10-12',
            $schedule
        );

        $this->assertSame('present', $status);
        $this->assertSame(0, $late);
    }

    public function test_attendance_resolves_work_date_for_night_shift(): void
    {
        $service = app(AttendanceService::class);
        $schedule = WorkSchedule::factory()->create([
            'check_in_time' => '22:00:00',
            'check_out_time' => '06:00:00',
            'crosses_midnight' => true,
            'work_days' => [1, 2, 3, 4, 5, 6, 7],
        ]);

        $earlyMorning = Carbon::parse('2026-10-13 01:30', 'Asia/Jakarta');
        $this->assertSame('2026-10-12', $service->resolveWorkDate($earlyMorning, $schedule));

        $daytime = Carbon::parse('2026-10-12 23:00', 'Asia/Jakarta');
        $this->assertSame('2026-10-12', $service->resolveWorkDate($daytime, $schedule));
    }

    public function test_attendance_is_scheduled_workday_matches_schedule(): void
    {
        $service = app(AttendanceService::class);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);

        $employee = Employee::factory()->create(['work_schedule_id' => $schedule->id]);
        $monday = Carbon::parse('2026-10-12', 'Asia/Jakarta'); // Senin
        $sunday = Carbon::parse('2026-10-11', 'Asia/Jakarta'); // Minggu

        $this->assertTrue($service->isScheduledWorkDay($employee, $monday));
        $this->assertFalse($service->isScheduledWorkDay($employee, $sunday));
    }

    public function test_leave_working_days_excludes_weekend_and_holiday(): void
    {
        $service = app(LeaveService::class);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);

        $employee = Employee::factory()->create(['work_schedule_id' => $schedule->id]);
        Holiday::create(['date' => '2026-10-13', 'name' => 'Libur Nasional', 'is_national' => true]);

        // 2026-10-12 Senin s.d. 2026-10-14 Rabu: Senin hadir, Selasa libur, Rabu hadir => 2
        $days = $service->calculateWorkingDays(
            $employee,
            Carbon::parse('2026-10-12', 'Asia/Jakarta'),
            Carbon::parse('2026-10-14', 'Asia/Jakarta')
        );

        $this->assertSame(2.0, $days);
    }

    public function test_leave_working_days_single_day_is_one(): void
    {
        $service = app(LeaveService::class);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);

        $employee = Employee::factory()->create(['work_schedule_id' => $schedule->id]);

        $days = $service->calculateWorkingDays(
            $employee,
            Carbon::parse('2026-10-12', 'Asia/Jakarta'),
            Carbon::parse('2026-10-12', 'Asia/Jakarta')
        );

        $this->assertSame(1.0, $days);
    }
}
