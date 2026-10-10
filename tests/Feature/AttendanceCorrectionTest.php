<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(string $role = 'karyawan', array $attrs = []): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $schedule = WorkSchedule::factory()->create();
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id, 'work_schedule_id' => $schedule->id], $attrs));

        return [$user, $employee];
    }

    public function test_employee_can_submit_correction(): void
    {
        $this->employeeUser('manager');
        [$user, $employee] = $this->employeeUser();
        app(AttendanceService::class)->checkIn($employee, '127.0.0.1');

        $response = $this->actingAs($user)->post('/attendance-corrections', [
            'work_date' => now()->toDateString(),
            'requested_check_in_at' => now()->toDateString().'T08:05',
            'reason' => 'Lupa tap masuk karena antrean.',
        ]);

        $response->assertRedirect('/attendance-corrections');
        $this->assertDatabaseHas('attendance_corrections', ['employee_id' => $employee->id, 'status' => 'pending']);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_correction_requires_reason(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/attendance-corrections', [
            'work_date' => now()->toDateString(),
        ])->assertSessionHasErrors('reason');
    }

    public function test_future_work_date_is_rejected(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/attendance-corrections', [
            'work_date' => now()->addDay()->toDateString(),
            'reason' => 'Tanggal masa depan.',
        ])->assertSessionHasErrors('work_date');
    }

    public function test_manager_can_approve_team_correction(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [, $member] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $member->user->assignRole('karyawan');
        $record = app(AttendanceService::class)->checkIn($member, '127.0.0.1');
        $correction = AttendanceCorrection::create([
            'attendance_record_id' => $record->id,
            'employee_id' => $member->id,
            'work_date' => $record->work_date->toDateString(),
            'requested_check_in_at' => $record->work_date->toDateString().' 08:05:00',
            'reason' => 'Telat tercatat sistem.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($managerUser)->patch("/attendance-corrections/{$correction->id}/decide", [
            'action' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertSame('approved', $correction->fresh()->status);
        $this->assertDatabaseHas('approval_actions', ['approvable_id' => $correction->id, 'action' => 'approved']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $member->user->id]);
    }

    public function test_notification_page_lists_user_notifications(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [, $member] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $member->user->assignRole('karyawan');
        $record = app(AttendanceService::class)->checkIn($member, '127.0.0.1');
        AttendanceCorrection::create([
            'attendance_record_id' => $record->id,
            'employee_id' => $member->id,
            'work_date' => $record->work_date->toDateString(),
            'reason' => 'Perlu diperbaiki.',
            'status' => 'pending',
        ]);
        $this->actingAs($member->user)->post('/attendance-corrections', [
            'work_date' => now()->toDateString(),
            'reason' => 'Pengajuan kedua.',
        ]);

        $this->actingAs($managerUser)->get('/notifications')->assertOk();
    }

    public function test_karyawan_cannot_decide_correction(): void
    {
        [$user, $employee] = $this->employeeUser();
        $correction = AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'reason' => 'Tes.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->patch("/attendance-corrections/{$correction->id}/decide", [
            'action' => 'approved',
        ])->assertForbidden();
    }

    public function test_processed_correction_cannot_be_redecided(): void
    {
        [$hrdUser] = $this->employeeUser('hrd_admin');
        [, $employee] = $this->employeeUser();
        $correction = AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'reason' => 'Tes.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($hrdUser)->patch("/attendance-corrections/{$correction->id}/decide", [
            'action' => 'rejected',
            'decision_note' => 'Ubah pikiran.',
        ]);

        $response->assertRedirect();
        $this->assertSame('approved', $correction->fresh()->status);
    }

    public function test_employee_sees_only_own_corrections(): void
    {
        [$userA, $empA] = $this->employeeUser();
        [, $empB] = $this->employeeUser();
        AttendanceCorrection::create(['employee_id' => $empA->id, 'work_date' => now()->toDateString(), 'reason' => 'ALASANUNIKA', 'status' => 'pending']);
        $other = AttendanceRecord::create(['employee_id' => $empB->id, 'work_date' => now()->toDateString(), 'status' => 'present']);
        AttendanceCorrection::create(['employee_id' => $empB->id, 'attendance_record_id' => $other->id, 'work_date' => now()->toDateString(), 'reason' => 'ALASANUNIKB', 'status' => 'approved']);

        $response = $this->actingAs($userA)->get('/attendance-corrections');

        $response->assertOk();
        $response->assertSee('ALASANUNIKA');
        $response->assertDontSee('ALASANUNIKB');
    }
}
