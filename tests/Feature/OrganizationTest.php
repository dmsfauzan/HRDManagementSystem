<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_hrd_can_create_department(): void
    {
        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/departments', [
            'code' => 'RND',
            'name' => 'Riset & Pengembangan',
        ]);

        $response->assertRedirect('/departments');
        $this->assertDatabaseHas('departments', ['code' => 'RND', 'name' => 'Riset & Pengembangan']);
    }

    public function test_duplicate_department_code_is_rejected(): void
    {
        Department::factory()->create(['code' => 'OPS']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/departments', [
            'code' => 'OPS',
            'name' => 'Operasional Baru',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        Employee::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->delete("/departments/{$department->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_karyawan_cannot_access_departments(): void
    {
        $this->actingAs($this->userWithRole('karyawan'))->get('/departments')->assertForbidden();
    }

    public function test_hrd_can_create_position(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/positions', [
            'code' => 'RND-ANL',
            'name' => 'Analis Riset',
            'department_id' => $department->id,
            'level' => 'Staff',
        ]);

        $response->assertRedirect('/positions');
        $this->assertDatabaseHas('positions', ['code' => 'RND-ANL']);
    }

    public function test_duplicate_position_code_is_rejected(): void
    {
        Position::factory()->create(['code' => 'HRD-STAFF']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/positions', [
            'code' => 'HRD-STAFF',
            'name' => 'Duplikat',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_position_with_employees_cannot_be_deleted(): void
    {
        $position = Position::factory()->create();
        Employee::factory()->create(['position_id' => $position->id]);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->delete("/positions/{$position->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_manager_cannot_access_positions(): void
    {
        $this->actingAs($this->userWithRole('manager'))->get('/positions')->assertForbidden();
    }
}
