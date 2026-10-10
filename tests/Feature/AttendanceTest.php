<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(string $role = 'karyawan', array $employeeAttrs = []): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $schedule = WorkSchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5],
        ]);
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employment_status' => 'active'], $employeeAttrs));

        return [$user, $employee];
    }

    public function test_employee_can_check_in(): void
    {
        [$user, $employee] = $this->employeeUser();

        $response = $this->actingAs($user)->post('/attendance/check-in');

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_records', ['employee_id' => $employee->id]);
    }

    public function test_double_check_in_is_rejected(): void
    {
        [$user, $employee] = $this->employeeUser();
        app(AttendanceService::class)->checkIn($employee, '127.0.0.1');

        $response = $this->actingAs($user)->post('/attendance/check-in');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_checkout_without_checkin_is_rejected(): void
    {
        [$user] = $this->employeeUser();

        $response = $this->actingAs($user)->post('/attendance/check-out');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_checkout_completes_open_session(): void
    {
        [$user, $employee] = $this->employeeUser();
        $service = app(AttendanceService::class);
        $record = $service->checkIn($employee, '127.0.0.1');

        $record->update(['check_in_at' => now()->subMinutes(30)]);
        $response = $this->actingAs($user)->post('/attendance/check-out');

        $response->assertRedirect();
        $this->assertNotNull($record->fresh()->check_out_at);
        $this->assertGreaterThan(0, $record->fresh()->work_minutes);
    }

    public function test_employee_can_view_own_attendance_but_not_others(): void
    {
        [$userA, $empA] = $this->employeeUser();
        [, $empB] = $this->employeeUser();
        $recA = app(AttendanceService::class)->checkIn($empA, '127.0.0.1');
        $recB = app(AttendanceService::class)->checkIn($empB, '127.0.0.1');

        $this->actingAs($userA)->get("/attendance/{$recB->id}")->assertForbidden();
        $this->actingAs($userA)->get("/attendance/{$recA->id}")->assertOk();
    }

    public function test_manager_index_shows_team_only(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        $member = Employee::factory()->create(['manager_id' => $manager->id]);
        $outsider = Employee::factory()->create();
        AttendanceRecord::create(['employee_id' => $member->id, 'work_date' => now()->toDateString(), 'status' => 'present']);
        AttendanceRecord::create(['employee_id' => $outsider->id, 'work_date' => now()->toDateString(), 'status' => 'present']);

        $response = $this->actingAs($managerUser)->get('/attendance?period=daily');

        $response->assertOk();
        $response->assertSee($member->full_name);
        $response->assertDontSee($outsider->full_name);
    }

    public function test_night_shift_check_in_early_morning_maps_to_previous_day(): void
    {
        $service = app(AttendanceService::class);
        $schedule = WorkSchedule::factory()->create([
            'check_in_time' => '22:00:00',
            'check_out_time' => '06:00:00',
            'crosses_midnight' => true,
            'work_days' => [1, 2, 3, 4, 5, 6, 7],
        ]);
        $now = now('Asia/Jakarta')->setTime(1, 30);
        $this->travelTo($now);

        $this->assertSame($now->copy()->subDay()->toDateString(), $service->resolveWorkDate($now, $schedule));
    }
}
