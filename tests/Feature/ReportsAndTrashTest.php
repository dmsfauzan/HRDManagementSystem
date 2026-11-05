<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JobApplication;
use App\Models\Overtime;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAndTrashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_recruitment_report_shows_funnel(): void
    {
        $application = JobApplication::factory()->create(['status' => 'hired', 'hired_at' => now()]);

        $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/recruitment')
            ->assertOk()
            ->assertSee('Laporan Rekrutmen')
            ->assertSee($application->candidate_name);
    }

    public function test_recruitment_report_export_csv(): void
    {
        JobApplication::factory()->create(['candidate_name' => 'Pelamar Ekspor']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/recruitment?format=csv');

        $response->assertOk();
        $this->assertStringContainsString('Pelamar Ekspor', $response->streamedContent());
    }

    public function test_overtime_report_lists_approved(): void
    {
        $overtime = Overtime::factory()->create(['status' => 'approved', 'date' => tz_today()->toDateString()]);

        $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/overtime?period=daily&date='.tz_today()->toDateString())
            ->assertOk()
            ->assertSee($overtime->employee->full_name);
    }

    public function test_manager_cannot_view_reports(): void
    {
        $this->actingAs($this->userWithRole('manager'))->get('/reports/recruitment')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/reports/overtime')->assertForbidden();
    }

    public function test_trash_lists_and_restores_department(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $department = Department::factory()->create(['name' => 'Departemen Arsip']);
        $department->delete();

        $this->actingAs($hrd)->get('/trash?type=departments')
            ->assertOk()
            ->assertSee('Departemen Arsip');

        $this->actingAs($hrd)->patch("/trash/departments/{$department->id}/restore")->assertRedirect();

        $this->assertNull($department->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'trash.restored']);
    }

    public function test_trash_rejects_unauthorized_type(): void
    {
        $user = $this->userWithRole('karyawan');

        $this->actingAs($user)->get('/trash')->assertForbidden();
    }

    public function test_only_super_admin_can_restore_users(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $target = User::factory()->create();
        $target->delete();

        $this->actingAs($hrd)->patch("/trash/users/{$target->id}/restore")->assertForbidden();

        $super = $this->userWithRole('super_admin');
        $this->actingAs($super)->patch("/trash/users/{$target->id}/restore")->assertRedirect();
        $this->assertNull($target->fresh()->deleted_at);
    }

    public function test_trash_shows_allowed_tabs_only(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $response = $this->actingAs($hrd)->get('/trash');

        $response->assertOk();
        $response->assertSee('Departemen');
        $response->assertDontSee('Pengguna');
    }
}
