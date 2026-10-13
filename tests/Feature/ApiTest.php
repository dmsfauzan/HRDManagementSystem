<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\ApiTokenService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function tokenFor(User $user): string
    {
        [, $plain] = app(ApiTokenService::class)->issue($user, 'test');

        return $plain;
    }

    private function employeeUser(array $attrs = []): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('karyawan');
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id, 'employment_status' => 'active'], $attrs));

        return [$user, $employee];
    }

    public function test_api_rejects_missing_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_api_rejects_invalid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer tidak-valid')->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_api_returns_profile_with_valid_token(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.employee.employee_id', $employee->employee_id);
    }

    public function test_api_attendance_checkin_flow(): void
    {
        [$user] = $this->employeeUser(['work_schedule_id' => null]);
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in')
            ->assertOk()
            ->assertJsonPath('data.status', 'present');

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_api_leave_balances_and_submission(): void
    {
        [$user] = $this->employeeUser();
        $token = $this->tokenFor($user);
        $annual = LeaveType::factory()->create(['code' => 'annual', 'tracks_balance' => true, 'default_quota' => 12]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/leave-balances')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/leaves', [
                'leave_type_id' => $annual->id,
                'start_date' => tz_today()->addDays(7)->toDateString(),
                'end_date' => tz_today()->addDays(9)->toDateString(),
                'reason' => 'Via API',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [, $plain] = app(ApiTokenService::class)->issue($user, 'expired', ['*'], now()->subDay());

        $this->withHeader('Authorization', 'Bearer '.$plain)->getJson('/api/v1/me')->assertStatus(401);
        $this->assertSame(1, ApiToken::count());
    }

    public function test_health_endpoint_ok(): void
    {
        $this->getJson('/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_user_can_create_and_revoke_api_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('karyawan');

        $this->actingAs($user)->post('/profile/api-tokens', ['name' => 'Mobile'])
            ->assertRedirect('/profile/api-tokens');

        $this->assertDatabaseHas('api_tokens', ['user_id' => $user->id, 'name' => 'Mobile']);

        $token = ApiToken::first();
        $this->actingAs($user)->delete("/profile/api-tokens/{$token->id}")->assertRedirect();
        $this->assertDatabaseCount('api_tokens', 0);
    }
}
