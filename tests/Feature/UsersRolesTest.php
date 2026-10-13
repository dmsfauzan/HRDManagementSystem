<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsersRolesTest extends TestCase
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

    public function test_super_admin_can_view_users(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))->get('/users')->assertOk();
    }

    public function test_hrd_cannot_access_users(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'))->get('/users')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/users')->assertForbidden();
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $response = $this->actingAs($this->userWithRole('super_admin'))->post('/users', [
            'name' => 'Manajer Baru',
            'email' => 'manajer.baru@hris.test',
            'password' => 'Rahasia123456',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $response->assertRedirect('/users');
        $user = User::where('email', 'manajer.baru@hris.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'ada@hris.test']);

        $this->actingAs($this->userWithRole('super_admin'))->post('/users', [
            'name' => 'Duplikat',
            'email' => 'ada@hris.test',
            'password' => 'Rahasia123456',
            'role' => 'karyawan',
        ])->assertSessionHasErrors('email');
    }

    public function test_super_admin_cannot_change_own_role(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'karyawan',
        ])->assertForbidden();
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->patch("/users/{$admin->id}/toggle-active")->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_role_change_is_audited(): void
    {
        $admin = $this->userWithRole('super_admin');
        $target = $this->userWithRole('karyawan');

        $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'manager',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertTrue($target->fresh()->hasRole('manager'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed']);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'nonaktif@hris.test',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);
        $user->assignRole('karyawan');

        $this->post('/login', ['email' => 'nonaktif@hris.test', 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_super_admin_can_reset_password(): void
    {
        $admin = $this->userWithRole('super_admin');
        $target = $this->userWithRole('karyawan');

        $response = $this->actingAs($admin)->patch("/users/{$target->id}/reset-password", [
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('PasswordBaru123', $target->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset']);
    }

    public function test_super_admin_can_view_roles_page(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))->get('/roles')->assertOk()->assertSee('Super Admin');
    }

    public function test_hrd_cannot_view_roles_page(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'))->get('/roles')->assertForbidden();
    }
}
