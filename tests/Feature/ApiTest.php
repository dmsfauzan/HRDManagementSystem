<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\ApiTokenService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_generated_token_is_hex_without_pipe(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [, $plain] = app(ApiTokenService::class)->issue($user, 'format');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $plain);
        $this->assertStringNotContainsString('|', $plain);
    }

    public function test_bearer_token_with_sanctum_style_prefix_is_accepted(): void
    {
        [$user] = $this->employeeUser();

        $this->withHeader('Authorization', 'Bearer 99|'.$this->tokenFor($user))
            ->getJson('/api/v1/me')
            ->assertOk();
    }

    public function test_api_login_returns_token_without_pipe(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => true,
        ]);
        $user->assignRole('karyawan');

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'Password12345',
            'device_name' => 'Mobile',
        ]);

        $response->assertOk()->assertJsonStructure([
            'success',
            'message',
            'data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email', 'roles', 'permissions']],
        ])->assertJsonPath('success', true);

        $token = $response->json('data.token');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertStringNotContainsString('|', $token);
        $this->assertContains('employees.view', $response->json('data.user.permissions'));

        // Token hasil login dapat dipakai untuk mengakses endpoint terproteksi.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_api_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'salah-password',
        ])->assertStatus(401);
    }

    public function test_api_login_rejects_inactive_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'Password12345',
        ])->assertStatus(403);
    }

    public function test_api_login_rejects_two_factor_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => true,
            'two_factor_enabled' => true,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'Password12345',
        ])->assertStatus(422);
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
