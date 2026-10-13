<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\ShiftSwap;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterShiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role, array $attrs = []): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id, 'employment_status' => 'active'], $attrs));

        return [$user, $employee];
    }

    public function test_hrd_can_save_roster_assignment(): void
    {
        $hrd = User::factory()->create(['is_active' => true]);
        $hrd->syncRoles(['hrd_admin']);
        $schedule = WorkSchedule::factory()->create();
        $employee = Employee::factory()->create();

        $week = '2026-10-12'; // Senin

        $this->actingAs($hrd)->post('/roster', [
            'week_start' => $week,
            'assignments' => [$employee->id => $schedule->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('schedule_assignments', [
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
        ]);
    }

    public function test_roster_assignment_overrides_schedule_for_attendance(): void
    {
        $default = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);
        $weekend = WorkSchedule::factory()->create(['work_days' => [6]]);
        $employee = Employee::factory()->create(['work_schedule_id' => $default->id]);

        $saturday = Carbon::parse('2026-10-10', tz()); // Sabtu
        $this->assertFalse(app(AttendanceService::class)->isScheduledWorkDay($employee, $saturday));

        ScheduleAssignment::create([
            'employee_id' => $employee->id,
            'week_start' => $saturday->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
            'work_schedule_id' => $weekend->id,
        ]);

        $this->assertTrue(app(AttendanceService::class)->isScheduledWorkDay($employee->fresh(), $saturday));
    }

    public function test_employee_cannot_access_roster(): void
    {
        [$user] = $this->userWithRole('karyawan');

        $this->actingAs($user)->get('/roster')->assertForbidden();
    }

    public function test_employee_can_submit_shift_swap(): void
    {
        [$user, $me] = $this->userWithRole('karyawan');
        $target = Employee::factory()->create();

        $this->actingAs($user)->post('/shift-swaps', [
            'target_employee_id' => $target->id,
            'date' => tz_today()->addDays(3)->toDateString(),
            'reason' => 'Ada keperluan keluarga.',
        ])->assertRedirect('/shift-swaps');

        $this->assertDatabaseHas('shift_swaps', [
            'requester_employee_id' => $me->id,
            'target_employee_id' => $target->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_swap_with_self(): void
    {
        [$user, $me] = $this->userWithRole('karyawan');

        $this->actingAs($user)->post('/shift-swaps', [
            'target_employee_id' => $me->id,
            'date' => tz_today()->addDays(3)->toDateString(),
            'reason' => 'Tes.',
        ])->assertSessionHasErrors('target_employee_id');
    }

    public function test_manager_approval_swaps_schedules(): void
    {
        [$managerUser, $manager] = $this->userWithRole('manager');
        [$empUser, $employee] = $this->userWithRole('karyawan', ['manager_id' => $manager->id]);
        $target = Employee::factory()->create();

        $scheduleA = WorkSchedule::factory()->create();
        $scheduleB = WorkSchedule::factory()->create();
        $employee->update(['work_schedule_id' => $scheduleA->id]);
        $target->update(['work_schedule_id' => $scheduleB->id]);

        $date = tz_today()->addDays(3);
        $week = $date->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();

        $swap = ShiftSwap::create([
            'requester_employee_id' => $employee->id,
            'target_employee_id' => $target->id,
            'date' => $date->toDateString(),
            'reason' => 'Tes tukar.',
            'status' => 'pending',
        ]);

        $this->actingAs($managerUser)->patch("/shift-swaps/{$swap->id}/decide", ['action' => 'approved'])->assertRedirect();

        $this->assertSame('approved', $swap->fresh()->status);
        $assigned = ScheduleAssignment::where('employee_id', $employee->id)->first();
        $this->assertNotNull($assigned);
        $this->assertSame($scheduleB->id, $assigned->work_schedule_id);
    }

    public function test_employee_cannot_decide_shift_swap(): void
    {
        [$user, $me] = $this->userWithRole('karyawan');
        $target = Employee::factory()->create();
        $swap = ShiftSwap::create([
            'requester_employee_id' => $me->id,
            'target_employee_id' => $target->id,
            'date' => tz_today()->addDays(3)->toDateString(),
            'reason' => 'Tes.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->patch("/shift-swaps/{$swap->id}/decide", ['action' => 'approved'])->assertForbidden();
    }
}
