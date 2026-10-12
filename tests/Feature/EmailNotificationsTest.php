<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Notifications\LeaveSubmitted;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_leave_submitted_notification_is_queued_and_has_mail_content(): void
    {
        Notification::fake();

        $this->userWithRole('manager');
        [$empUser] = $this->employeeWithAnnual();

        $annual = LeaveType::where('code', 'annual')->firstOrFail();

        $this->actingAs($empUser)->post('/leave', [
            'leave_type_id' => $annual->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'reason' => 'Keperluan keluarga',
        ]);

        Notification::assertSentTo(
            User::permission('leave.approve')->get(),
            LeaveSubmitted::class
        );
    }

    public function test_leave_submitted_mail_has_action_url(): void
    {
        $annual = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);
        $employee = Employee::factory()->create(['work_schedule_id' => $schedule->id]);
        $manager = User::factory()->create(['email' => 'mgr@example.com']);
        $manager->assignRole('manager');

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'year' => 2026,
            'balance' => 12,
            'used' => 0,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'total_days' => 3.00,
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $notification = new LeaveSubmitted($leave);
        $mailMessage = $notification->toMail($manager);

        $this->assertStringContainsString(route('leave.show', $leave), $mailMessage->actionUrl);
        $this->assertStringContainsString('Pengajuan Cuti', (string) $mailMessage->subject);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    private function employeeWithAnnual(): array
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employment_status' => 'active']);
        $annual = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'year' => 2026,
            'balance' => 12,
            'used' => 0,
        ]);

        return [$user, $employee];
    }
}
