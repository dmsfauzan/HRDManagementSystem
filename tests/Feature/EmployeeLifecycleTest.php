<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
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

    public function test_hrd_can_offboard_employee_and_disable_account(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $account = User::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $account->id, 'employment_status' => 'active']);

        $this->actingAs($hrd)->patch("/employees/{$employee->id}/offboard", ['reason' => 'Resign'])->assertRedirect();

        $this->assertSame('resigned', $employee->fresh()->employment_status);
        $this->assertFalse((bool) $account->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.offboarded']);
    }

    public function test_bulk_deactivate_processes_selected_employees(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $a = Employee::factory()->create(['employment_status' => 'active']);
        $b = Employee::factory()->create(['employment_status' => 'active']);

        $this->actingAs($hrd)->post('/employees/bulk', [
            'action' => 'deactivate',
            'ids' => [$a->id, $b->id],
        ])->assertRedirect();

        $this->assertSame('inactive', $a->fresh()->employment_status);
        $this->assertSame('inactive', $b->fresh()->employment_status);
    }

    public function test_bulk_requires_selection(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $this->actingAs($hrd)->post('/employees/bulk', ['action' => 'deactivate', 'ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_hrd_can_restore_trashed_employee(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $employee = Employee::factory()->create();
        $employee->delete();

        $this->actingAs($hrd)->get('/employees/trashed')->assertOk()->assertSee($employee->full_name);
        $this->actingAs($hrd)->patch("/employees/{$employee->id}/restore")->assertRedirect();

        $this->assertNull($employee->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.restored']);
    }

    public function test_org_chart_renders_hierarchy(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $boss = Employee::factory()->create(['full_name' => 'Atasan Utama', 'manager_id' => null]);
        Employee::factory()->create(['full_name' => 'Anak Buah', 'manager_id' => $boss->id]);

        $this->actingAs($hrd)->get('/org-chart')
            ->assertOk()
            ->assertSee('Atasan Utama')
            ->assertSee('Anak Buah');
    }

    public function test_password_policy_rejects_weak_password(): void
    {
        $admin = $this->userWithRole('super_admin');
        $target = $this->userWithRole('karyawan');

        $this->actingAs($admin)->patch("/users/{$target->id}/reset-password", [
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->patch("/users/{$target->id}/reset-password", [
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('StrongPass123', $target->fresh()->password));
    }

    public function test_contract_reminder_notifies_hrd(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $employee = Employee::factory()->create([
            'employment_status' => 'active',
            'contract_end_date' => tz_today()->addDays(10)->toDateString(),
        ]);

        $this->artisan('contracts:remind')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $hrd->id]);
        $this->assertNotNull($employee->fresh()->contract_reminded_at);
    }

    public function test_contract_reminder_ignores_far_dates(): void
    {
        $this->userWithRole('hrd_admin');
        Employee::factory()->create([
            'employment_status' => 'active',
            'contract_end_date' => tz_today()->addDays(120)->toDateString(),
        ]);

        $this->artisan('contracts:remind')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }
}
