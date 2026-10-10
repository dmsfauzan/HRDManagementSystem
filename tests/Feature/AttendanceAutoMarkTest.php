<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\WorkSchedule;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAutoMarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function scheduledEmployee(array $attrs = []): Employee
    {
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);

        return Employee::factory()->create(array_merge([
            'work_schedule_id' => $schedule->id,
            'employment_status' => 'active',
        ], $attrs));
    }

    public function test_marks_absent_for_scheduled_workday_without_checkin(): void
    {
        $employee = $this->scheduledEmployee();

        $this->artisan('attendance:mark-absent', ['--date' => '2026-10-12'])->assertSuccessful();

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'status' => 'absent',
        ]);
        $this->assertSame('2026-10-12', AttendanceRecord::first()->work_date->toDateString());
    }

    public function test_skips_weekend(): void
    {
        $this->scheduledEmployee();

        $this->artisan('attendance:mark-absent', ['--date' => '2026-10-10'])->assertSuccessful();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_skips_holiday(): void
    {
        $this->scheduledEmployee();
        Holiday::create(['date' => '2026-10-12', 'name' => 'Cuti Bersama', 'is_national' => true]);

        $this->artisan('attendance:mark-absent', ['--date' => '2026-10-12'])->assertSuccessful();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_marks_leave_when_approved_leave_covers_date(): void
    {
        $employee = $this->scheduledEmployee();
        $type = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $type->id,
            'start_date' => '2026-10-12', 'end_date' => '2026-10-13',
            'total_days' => 2, 'reason' => 'Cuti', 'status' => 'approved',
        ]);

        $this->artisan('attendance:mark-absent', ['--date' => '2026-10-12'])->assertSuccessful();

        $this->assertDatabaseHas('attendance_records', ['employee_id' => $employee->id, 'status' => 'leave']);
    }

    public function test_does_not_overwrite_existing_record(): void
    {
        $employee = $this->scheduledEmployee();
        AttendanceRecord::create(['employee_id' => $employee->id, 'work_date' => '2026-10-12', 'status' => 'present']);

        $this->artisan('attendance:mark-absent', ['--date' => '2026-10-12'])->assertSuccessful();

        $this->assertSame('present', AttendanceRecord::first()->status);
        $this->assertDatabaseCount('attendance_records', 1);
    }
}
