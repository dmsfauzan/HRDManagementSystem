<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSearchSortTest extends TestCase
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

    public function test_global_search_requires_auth(): void
    {
        $this->get('/search?q=andi')->assertRedirect('/login');
    }

    public function test_hrd_global_search_finds_employees(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        Employee::factory()->create(['full_name' => 'Andi Wijaya', 'employee_id' => 'IT-900']);

        $this->actingAs($hrd)->getJson('/search?q=Andi')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Andi Wijaya']);
    }

    public function test_karyawan_global_search_only_finds_self(): void
    {
        $user = $this->userWithRole('karyawan');
        $me = Employee::factory()->create(['user_id' => $user->id, 'full_name' => 'Saya Sendiri']);
        Employee::factory()->create(['full_name' => 'Orang Lain']);

        $response = $this->actingAs($user)->getJson('/search?q=Orang');

        $response->assertOk();
        $this->assertStringNotContainsString('Orang Lain', $response->getContent());
        $this->assertSame($me->id, $user->employee->id);
    }

    public function test_employee_list_can_be_sorted(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        Employee::factory()->create(['full_name' => 'Zeta Terakhir']);
        Employee::factory()->create(['full_name' => 'Alpha Pertama']);

        $this->actingAs($hrd)->get('/employees?sort=full_name&direction=asc')
            ->assertOk()
            ->assertSeeInOrder(['Alpha Pertama', 'Zeta Terakhir']);
    }

    public function test_search_palette_and_toast_render_in_layout(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $this->actingAs($hrd)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pencarian global');
    }

    public function test_breadcrumbs_render_on_employee_detail(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $employee = Employee::factory()->create(['full_name' => 'Breadcrumb Karyawan']);

        $this->actingAs($hrd)->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('Breadcrumb Karyawan');
    }
}
