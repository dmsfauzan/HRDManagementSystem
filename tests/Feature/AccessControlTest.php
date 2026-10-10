<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_inactive_user_is_logged_out_and_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->from('/dashboard')->actingAs($user)->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    #[DataProvider('rolesProvider')]
    public function test_dashboard_is_accessible_for_each_role(string $role): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public static function rolesProvider(): array
    {
        return [
            'super admin' => ['super_admin'],
            'hrd admin' => ['hrd_admin'],
            'manager' => ['manager'],
            'karyawan' => ['karyawan'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }
}
