<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\EmployeeService;
use App\Services\LeaveService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalancePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function annualType(array $overrides = []): LeaveType
    {
        return LeaveType::create(array_merge([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'carry_max_days' => 2,
            'requires_attachment' => false, 'is_active' => true,
        ], $overrides));
    }

    public function test_prorated_quota_for_mid_year_join(): void
    {
        $type = $this->annualType();
        $employee = Employee::factory()->create(['join_date' => '2026-07-01']);

        app(LeaveService::class)->ensureBalances($employee, 2026);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('year', 2026)->first();
        // (13 - 7) / 12 * 12 = 6 hari
        $this->assertEquals(6, (float) $balance->balance);
    }

    public function test_full_quota_for_previous_year_join(): void
    {
        $type = $this->annualType();
        $employee = Employee::factory()->create(['join_date' => '2024-03-01']);

        app(LeaveService::class)->ensureBalances($employee, 2026);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('year', 2026)->first();
        $this->assertEquals(12, (float) $balance->balance);
    }

    public function test_employee_service_create_applies_prorate(): void
    {
        $this->annualType();
        $service = app(EmployeeService::class);

        $employee = $service->create([
            'employee_id' => 'PRORATE-1',
            'full_name' => 'Karyawan Baru',
            'join_date' => '2026-10-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        // (13 - 10) / 12 * 12 = 3 hari
        $this->assertEquals(3, (float) $balance->balance);
    }

    public function test_carry_over_moves_capped_balance_and_expires_remainder(): void
    {
        $type = $this->annualType(['carry_max_days' => 2]);
        $employee = Employee::factory()->create();

        $prev = LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => 2025,
            'balance' => 5,
            'used' => 0,
        ]);

        $result = app(LeaveService::class)->carryOver(2026);

        $this->assertSame(1, $result['carried']);
        $this->assertSame(1, $result['expired']);

        $this->assertEquals(0, (float) $prev->fresh()->balance);

        $target = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('year', 2026)->first();
        $this->assertEquals(2, (float) $target->balance);

        $this->assertDatabaseHas('leave_balance_transactions', [
            'leave_balance_id' => $target->id,
            'type' => 'credit',
            'amount' => 2,
        ]);
        $this->assertDatabaseHas('leave_balance_transactions', [
            'leave_balance_id' => $prev->id,
            'type' => 'expired',
        ]);
    }

    public function test_carry_over_ignores_zero_balance(): void
    {
        $type = $this->annualType();
        $employee = Employee::factory()->create();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2025, 'balance' => 0, 'used' => 12]);

        $result = app(LeaveService::class)->carryOver(2026);

        $this->assertSame(0, $result['carried']);
        $this->assertSame(0, $result['expired']);
    }
}
