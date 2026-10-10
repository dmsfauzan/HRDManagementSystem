<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
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

    public function test_hrd_can_manage_holidays(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $this->actingAs($hrd)->post('/holidays', ['date' => '2026-12-25', 'name' => 'Natal'])->assertRedirect('/holidays');
        $this->assertDatabaseHas('holidays', ['name' => 'Natal']);

        $holiday = Holiday::first();
        $this->actingAs($hrd)->put("/holidays/{$holiday->id}", ['date' => '2026-12-25', 'name' => 'Hari Natal'])->assertRedirect();
        $this->assertSame('Hari Natal', $holiday->fresh()->name);

        $this->actingAs($hrd)->delete("/holidays/{$holiday->id}")->assertRedirect();
        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_duplicate_holiday_date_rejected(): void
    {
        Holiday::create(['date' => '2026-01-01', 'name' => 'Tahun Baru']);

        $this->actingAs($this->userWithRole('hrd_admin'))
            ->post('/holidays', ['date' => '2026-01-01', 'name' => 'Duplikat'])
            ->assertSessionHasErrors('date');
    }

    public function test_manager_cannot_manage_holidays(): void
    {
        $this->actingAs($this->userWithRole('manager'))->get('/holidays')->assertForbidden();
    }

    public function test_hrd_can_manage_work_schedules(): void
    {
        $hrd = $this->userWithRole('hrd_admin');

        $response = $this->actingAs($hrd)->post('/work-schedules', [
            'code' => 'PAGI',
            'name' => 'Shift Pagi',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [1, 2, 3, 4, 5],
            'is_active' => true,
        ]);

        $response->assertRedirect('/work-schedules');
        $this->assertDatabaseHas('work_schedules', ['code' => 'PAGI', 'name' => 'Shift Pagi']);
        $this->assertSame([1, 2, 3, 4, 5], WorkSchedule::where('code', 'PAGI')->first()->work_days);
    }

    public function test_work_schedule_requires_at_least_one_work_day(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'))->post('/work-schedules', [
            'code' => 'KOSONG',
            'name' => 'Tanpa Hari',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'work_days' => [],
        ])->assertSessionHasErrors('work_days');
    }

    public function test_employee_cannot_manage_work_schedules(): void
    {
        $this->actingAs($this->userWithRole('karyawan'))->get('/work-schedules')->assertForbidden();
    }
}
