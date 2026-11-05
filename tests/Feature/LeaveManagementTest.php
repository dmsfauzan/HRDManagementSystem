<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    private LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->annual = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
    }

    private function hrdUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('hrd_admin');

        return $user;
    }

    private function employeeUser(string $role = 'karyawan', array $attrs = []): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);
        $employee = Employee::factory()->create(array_merge([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employment_status' => 'active',
        ], $attrs));

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'year' => 2026,
            'balance' => 12,
            'used' => 0,
        ]);

        return [$user, $employee];
    }

    public function test_employee_can_submit_leave_request(): void
    {
        [$user, $employee] = $this->employeeUser();

        $response = $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Keperluan keluarga.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'status' => 'pending',
            'total_days' => 3.00,
        ]);
    }

    public function test_end_date_before_start_is_rejected(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-14',
            'end_date' => '2026-10-12',
            'reason' => 'Tes.',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_insufficient_balance_is_rejected(): void
    {
        [$user, $employee] = $this->employeeUser();
        LeaveBalance::where('employee_id', $employee->id)->update(['balance' => 1]);

        $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Tes saldo kurang.',
        ])->assertSessionHasErrors('leave_type_id');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_attachment_required_type_is_enforced(): void
    {
        $sick = LeaveType::create([
            'code' => 'sick', 'name' => 'Cuti Sakit', 'is_paid' => true,
            'default_quota' => 0, 'tracks_balance' => false, 'requires_attachment' => true, 'is_active' => true,
        ]);
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $sick->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-13',
            'reason' => 'Sakit.',
        ])->assertSessionHasErrors('attachment');
    }

    public function test_manager_approval_decrements_balance_and_logs_transaction(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [$empUser, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $hrdUser = $this->hrdUser();

        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Cuti.',
        ]);
        $leave = LeaveRequest::first();

        // Level 1 (Manager) menyetujui; pengajuan masih menunggu level 2 (HRD).
        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved'])->assertRedirect();
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertEquals(0, (float) LeaveBalance::where('employee_id', $employee->id)->value('used'));

        // Level 2 (HRD) menyetujui; saldo baru dipotong setelah semua level setuju.
        $this->actingAs($hrdUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved'])->assertRedirect();

        $this->assertSame('approved', $leave->fresh()->status);
        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $this->assertEquals(9, (float) $balance->balance);
        $this->assertEquals(3, (float) $balance->used);
        $this->assertDatabaseHas('leave_balance_transactions', ['leave_request_id' => $leave->id, 'type' => 'debit']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $empUser->id]);
    }

    public function test_short_leave_needs_single_level_approval(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [$empUser, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);

        // 2 hari kerja (di bawah ambang 3 hari) -> cukup Manager.
        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-13',
            'reason' => 'Cuti singkat.',
        ]);
        $leave = LeaveRequest::first();

        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved'])->assertRedirect();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertEquals(10, (float) LeaveBalance::where('employee_id', $employee->id)->value('balance'));
    }

    public function test_rejection_does_not_change_balance(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [$empUser, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);

        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Cuti.',
        ]);
        $leave = LeaveRequest::first();

        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", [
            'action' => 'rejected',
            'decision_note' => 'Beban kerja tinggi.',
        ]);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $this->assertEquals(12, (float) $balance->balance);
        $this->assertEquals(0, (float) $balance->used);
        $this->assertDatabaseMissing('leave_balance_transactions', ['leave_request_id' => $leave->id, 'type' => 'debit']);
    }

    public function test_balance_is_not_deducted_twice(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [$empUser, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $hrdUser = $this->hrdUser();
        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Cuti.',
        ]);
        $leave = LeaveRequest::first();

        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved']);
        $this->actingAs($hrdUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved']);
        // Persetujuan ulang tidak boleh memotong saldo dua kali.
        $this->actingAs($hrdUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved']);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $this->assertEquals(9, (float) $balance->balance);
        $this->assertEquals(1, LeaveBalanceTransaction::where('leave_request_id', $leave->id)->count());
    }

    public function test_employee_cannot_decide_leave(): void
    {
        [$user, $employee] = $this->employeeUser();
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'total_days' => 3,
            'reason' => 'Tes.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->patch("/leave/{$leave->id}/decide", ['action' => 'approved'])->assertForbidden();
    }

    public function test_manager_cannot_decide_non_team_leave(): void
    {
        [$managerUser] = $this->employeeUser('manager');
        [, $other] = $this->employeeUser();
        $leave = LeaveRequest::create([
            'employee_id' => $other->id,
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'total_days' => 3,
            'reason' => 'Tes.',
            'status' => 'pending',
        ]);

        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved'])->assertForbidden();
    }

    public function test_cancelling_approved_leave_refunds_balance(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [$empUser, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $this->annual->id,
            'duration_type' => 'full_day',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'reason' => 'Cuti.',
        ]);
        $leave = LeaveRequest::first();
        $this->actingAs($managerUser)->patch("/leave/{$leave->id}/decide", ['action' => 'approved']);
        $this->actingAs($this->hrdUser())->patch("/leave/{$leave->id}/decide", ['action' => 'approved']);
        $this->assertSame('approved', $leave->fresh()->status);

        $this->actingAs($empUser)->patch("/leave/{$leave->id}/cancel")->assertRedirect();

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $this->assertEquals(12, (float) $balance->balance);
        $this->assertEquals('cancelled', $leave->fresh()->status);
        $this->assertDatabaseHas('leave_balance_transactions', ['leave_request_id' => $leave->id, 'type' => 'credit']);
    }

    public function test_employee_can_view_own_leave_only(): void
    {
        [$userA, $empA] = $this->employeeUser();
        [, $empB] = $this->employeeUser();
        $mine = LeaveRequest::create(['employee_id' => $empA->id, 'leave_type_id' => $this->annual->id, 'start_date' => '2026-10-12', 'end_date' => '2026-10-13', 'total_days' => 2, 'reason' => 'Punya saya.', 'status' => 'pending']);
        $other = LeaveRequest::create(['employee_id' => $empB->id, 'leave_type_id' => $this->annual->id, 'start_date' => '2026-10-12', 'end_date' => '2026-10-13', 'total_days' => 2, 'reason' => 'Punya orang lain.', 'status' => 'pending']);

        $this->actingAs($userA)->get("/leave/{$mine->id}")->assertOk();
        $this->actingAs($userA)->get("/leave/{$other->id}")->assertForbidden();
    }
}
