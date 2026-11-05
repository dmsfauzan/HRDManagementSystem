<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\ApiTokenService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenScopeTest extends TestCase
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
        Employee::factory()->create([
            'user_id' => $user->id,
            'employment_status' => 'active',
            'work_schedule_id' => null,
        ]);

        return $user;
    }

    public function test_read_only_token_cannot_write_attendance(): void
    {
        $user = $this->employeeUser();
        [, $plain] = app(ApiTokenService::class)->issue($user, 'read', ['profile:read', 'attendance:read']);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/attendance')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/attendance/check-in')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_full_token_can_write_attendance(): void
    {
        $user = $this->employeeUser();
        [, $plain] = app(ApiTokenService::class)->issue($user, 'full', ['*']);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/attendance/check-in')
            ->assertOk();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->employeeUser();
        [, $plain] = app(ApiTokenService::class)->issue($user, 'logout', ['*']);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    public function test_api_errors_are_in_indonesian(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    public function test_security_headers_include_csp_report_only(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }
}
