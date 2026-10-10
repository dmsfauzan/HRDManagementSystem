<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
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

    public function test_hrd_can_view_employee_index(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->get('/employees');

        $response->assertStatus(200);
    }

    public function test_hrd_can_create_employee(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/employees', [
            'employee_id' => 'EMP-100',
            'full_name' => 'Rina Marlina',
            'email' => 'rina@hris.test',
            'department_id' => $department->id,
            'join_date' => '2024-01-15',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['employee_id' => 'EMP-100', 'full_name' => 'Rina Marlina']);
    }

    public function test_duplicate_employee_id_is_rejected(): void
    {
        Employee::factory()->create(['employee_id' => 'EMP-200']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/employees', [
            'employee_id' => 'EMP-200',
            'full_name' => 'Duplikat',
            'join_date' => '2024-01-15',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_creating_employee_with_login_account_assigns_role(): void
    {
        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/employees', [
            'employee_id' => 'EMP-300',
            'full_name' => 'Budi Baru',
            'join_date' => '2024-02-01',
            'employment_type' => 'contract',
            'employment_status' => 'active',
            'create_account' => true,
            'account_email' => 'budi.baru@hris.test',
            'account_password' => 'rahasia123',
            'account_role' => 'karyawan',
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'budi.baru@hris.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('karyawan'));
        $this->assertDatabaseHas('employees', ['employee_id' => 'EMP-300', 'user_id' => $user->id]);
    }

    public function test_employee_cannot_view_other_employee(): void
    {
        $userA = $this->userWithRole('karyawan');
        $empA = Employee::factory()->create(['user_id' => $userA->id]);
        $empB = Employee::factory()->create();

        $this->actingAs($userA)->get("/employees/{$empB->id}")->assertForbidden();
        $this->actingAs($userA)->get("/employees/{$empA->id}")->assertOk();
    }

    public function test_employee_index_redirects_employee_to_own_profile(): void
    {
        $user = $this->userWithRole('karyawan');
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/employees')->assertRedirect("/employees/{$employee->id}");
    }

    public function test_manager_can_view_team_member_only(): void
    {
        $managerUser = $this->userWithRole('manager');
        $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
        $member = Employee::factory()->create(['manager_id' => $manager->id]);
        $outsider = Employee::factory()->create();

        $this->actingAs($managerUser)->get("/employees/{$member->id}")->assertOk();
        $this->actingAs($managerUser)->get("/employees/{$outsider->id}")->assertForbidden();
    }

    public function test_employee_role_cannot_create_employee(): void
    {
        $this->actingAs($this->userWithRole('karyawan'))->post('/employees', [
            'employee_id' => 'EMP-999',
            'full_name' => 'Curang',
            'join_date' => '2024-01-15',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ])->assertForbidden();
    }

    public function test_deactivating_employee_preserves_attendance_history(): void
    {
        $employee = Employee::factory()->create(['employment_status' => 'active']);
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
        ]);
        $user = User::factory()->create(['is_active' => true]);
        $employee->update(['user_id' => $user->id]);

        $this->actingAs($this->userWithRole('hrd_admin'))
            ->patch("/employees/{$employee->id}/deactivate")
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'employment_status' => 'inactive']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_photo_upload_must_be_valid_image(): void
    {
        Storage::fake('private');

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/employees', [
            'employee_id' => 'EMP-400',
            'full_name' => 'Foto Salah',
            'join_date' => '2024-01-15',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('photo');
    }

    public function test_hrd_can_upload_valid_photo(): void
    {
        Storage::fake('private');

        $response = $this->actingAs($this->userWithRole('hrd_admin'))->post('/employees', [
            'employee_id' => 'EMP-500',
            'full_name' => 'Punya Foto',
            'join_date' => '2024-01-15',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'photo' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $employee = Employee::where('employee_id', 'EMP-500')->first();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('private')->assertExists($employee->photo_path);
    }
}
