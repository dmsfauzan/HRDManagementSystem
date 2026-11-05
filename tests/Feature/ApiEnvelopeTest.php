<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('karyawan');
        Employee::factory()->create(['user_id' => $user->id, 'employment_status' => 'active', 'work_schedule_id' => null]);

        return $user;
    }

    public function test_unauthorized_uses_envelope(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_validation_error_uses_envelope(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'bukan-email'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_profile_includes_permissions(): void
    {
        $user = $this->employeeUser();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'name', 'email', 'roles', 'permissions']])
            ->assertJsonPath('data.roles.0', 'karyawan');
    }

    public function test_attendance_endpoints_use_envelope(): void
    {
        $user = $this->employeeUser();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['current_page', 'last_page', 'total']]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['work_date', 'check_in_at', 'status']]);
    }

    public function test_announcements_use_envelope(): void
    {
        $user = $this->employeeUser();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'meta']);
    }

    private function loginToken(User $user): string
    {
        $user->update(['password' => Hash::make('Password12345')]);

        return $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'Password12345',
        ])->json('data.token');
    }
}
