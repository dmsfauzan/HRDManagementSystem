<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HalfDayScopingRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(string $role = 'karyawan', array $attrs = []): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $schedule = WorkSchedule::factory()->create(['work_days' => [1, 2, 3, 4, 5]]);
        $employee = Employee::factory()->create(array_merge([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employment_status' => 'active',
        ], $attrs));

        return [$user, $employee];
    }

    public function test_half_day_leave_counts_as_half_day(): void
    {
        $type = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'carry_max_days' => 2,
            'requires_attachment' => false, 'is_active' => true,
        ]);
        [$user, $employee] = $this->employeeUser();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2026, 'balance' => 12, 'used' => 0]);

        $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'duration_type' => 'half_day_am',
            'reason' => 'Urusan pagi.',
        ])->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'duration_type' => 'half_day_am',
            'total_days' => 0.50,
        ]);
    }

    public function test_half_day_cannot_span_multiple_days(): void
    {
        $type = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/leave', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-13',
            'duration_type' => 'half_day_am',
            'reason' => 'Tes.',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_manager_only_sees_applications_in_own_department(): void
    {
        $deptA = Department::factory()->create();
        $deptB = Department::factory()->create();

        [$managerUser, $manager] = $this->employeeUser('manager', ['department_id' => $deptA->id]);

        $postingB = JobPosting::factory()->create(['department_id' => $deptB->id]);
        $applicationB = JobApplication::factory()->create(['job_posting_id' => $postingB->id]);

        $postingA = JobPosting::factory()->create(['department_id' => $deptA->id]);
        $applicationA = JobApplication::factory()->create(['job_posting_id' => $postingA->id]);

        $this->actingAs($managerUser)->get("/recruitment/applications/{$applicationB->id}")->assertForbidden();
        $this->actingAs($managerUser)->get("/recruitment/applications/{$applicationA->id}")->assertOk();

        $this->actingAs($managerUser)->get('/recruitment/applications')
            ->assertOk()
            ->assertSee($applicationA->candidate_name)
            ->assertDontSee($applicationB->candidate_name);
    }

    public function test_employee_reading_announcements_marks_as_read(): void
    {
        $announcement = Announcement::factory()->create(['is_active' => true, 'published_at' => now()]);
        [$user] = $this->employeeUser();

        $this->actingAs($user)->get('/announcements')->assertOk();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_purge_removes_old_rejected_applications_and_files(): void
    {
        Storage::fake('private');

        $posting = JobPosting::factory()->create();

        $old = JobApplication::factory()->create([
            'job_posting_id' => $posting->id,
            'status' => 'rejected',
            'created_at' => now()->subMonths(14),
            'cv_path' => 'recruitment/old/cv.pdf',
        ]);
        Storage::disk('private')->put('recruitment/old/cv.pdf', 'x');

        $recent = JobApplication::factory()->create([
            'job_posting_id' => $posting->id,
            'status' => 'rejected',
            'created_at' => now()->subMonth(),
        ]);

        $hiredOld = JobApplication::factory()->create([
            'job_posting_id' => $posting->id,
            'status' => 'hired',
            'created_at' => now()->subMonths(20),
        ]);

        $this->artisan('recruitment:purge', ['--months' => 12])->assertSuccessful();

        $this->assertDatabaseMissing('job_applications', ['id' => $old->id]);
        Storage::disk('private')->assertMissing('recruitment/old/cv.pdf');
        $this->assertDatabaseHas('job_applications', ['id' => $recent->id]);
        $this->assertDatabaseHas('job_applications', ['id' => $hiredOld->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'recruitment.purged']);
    }

    public function test_purge_dry_run_deletes_nothing(): void
    {
        Storage::fake('private');
        $posting = JobPosting::factory()->create();
        $old = JobApplication::factory()->create([
            'job_posting_id' => $posting->id,
            'status' => 'withdrawn',
            'created_at' => now()->subMonths(14),
        ]);

        $this->artisan('recruitment:purge', ['--months' => 12, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('job_applications', ['id' => $old->id]);
    }
}
