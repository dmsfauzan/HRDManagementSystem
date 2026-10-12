<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(string $role = 'karyawan', array $attrs = []): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id, 'employment_status' => 'active'], $attrs));

        return [$user, $employee];
    }

    public function test_employee_can_submit_overtime(): void
    {
        [$user, $employee] = $this->employeeUser();

        $response = $this->actingAs($user)->post('/overtime', [
            'date' => now()->subDay()->toDateString(),
            'start_at' => '17:00',
            'end_at' => '19:00',
            'reason' => 'Menyelesaikan laporan bulanan.',
        ]);

        $response->assertRedirect('/overtime');
        $this->assertDatabaseHas('overtimes', ['employee_id' => $employee->id, 'status' => 'pending', 'duration_minutes' => 120]);
    }

    public function test_short_duration_is_rejected(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post('/overtime', [
            'date' => now()->subDay()->toDateString(),
            'start_at' => '17:00',
            'end_at' => '17:10',
            'reason' => 'Terlalu singkat.',
        ])->assertSessionHasErrors('end_at');
    }

    public function test_overlapping_overtime_is_rejected(): void
    {
        [$user, $employee] = $this->employeeUser();
        Overtime::factory()->create([
            'employee_id' => $employee->id,
            'date' => now()->subDay()->toDateString(),
            'start_at' => '17:00:00',
            'end_at' => '19:00:00',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post('/overtime', [
            'date' => now()->subDay()->toDateString(),
            'start_at' => '18:00',
            'end_at' => '20:00',
            'reason' => 'Tumpang tindih.',
        ])->assertSessionHasErrors('start_at');
    }

    public function test_manager_can_approve_team_overtime(): void
    {
        [$managerUser, $manager] = $this->employeeUser('manager');
        [, $employee] = $this->employeeUser('karyawan', ['manager_id' => $manager->id]);
        $overtime = Overtime::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($managerUser)->patch("/overtime/{$overtime->id}/decide", ['action' => 'approved'])->assertRedirect();

        $this->assertSame('approved', $overtime->fresh()->status);
        $this->assertDatabaseHas('approval_actions', ['approvable_id' => $overtime->id, 'action' => 'approved']);
    }

    public function test_employee_cannot_decide_overtime(): void
    {
        [$user, $employee] = $this->employeeUser();
        $overtime = Overtime::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->patch("/overtime/{$overtime->id}/decide", ['action' => 'approved'])->assertForbidden();
    }

    public function test_processed_overtime_cannot_be_redecided(): void
    {
        [$hrdUser] = $this->employeeUser('hrd_admin');
        [, $employee] = $this->employeeUser();
        $overtime = Overtime::factory()->create(['employee_id' => $employee->id, 'status' => 'approved']);

        $response = $this->actingAs($hrdUser)->patch("/overtime/{$overtime->id}/decide", ['action' => 'rejected', 'decision_note' => 'ubah']);

        $response->assertRedirect();
        $this->assertSame('approved', $overtime->fresh()->status);
    }

    public function test_employee_sees_only_own_overtime(): void
    {
        [$userA, $empA] = $this->employeeUser('karyawan', ['full_name' => 'Karyawan AAA']);
        [, $empB] = $this->employeeUser('karyawan', ['full_name' => 'Karyawan BBB']);
        Overtime::factory()->create(['employee_id' => $empA->id]);
        Overtime::factory()->create(['employee_id' => $empB->id]);

        $response = $this->actingAs($userA)->get('/overtime');

        $response->assertOk();
        $response->assertSee('Karyawan AAA');
        $response->assertDontSee('Karyawan BBB');
    }
}
