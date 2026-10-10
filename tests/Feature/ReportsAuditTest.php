<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAuditTest extends TestCase
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

    public function test_hrd_can_view_reports_index(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'))->get('/reports')->assertOk();
    }

    public function test_manager_cannot_view_reports(): void
    {
        $this->actingAs($this->userWithRole('manager'))->get('/reports')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/reports/employees')->assertForbidden();
    }

    public function test_employee_report_export_returns_csv(): void
    {
        Employee::factory()->create(['full_name' => 'Ali Contoh', 'employee_id' => 'EMP-1']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/employees?export=csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Ali Contoh', $response->streamedContent());
        // Ekspor tercatat di audit
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_attendance_report_export_respects_status_filter(): void
    {
        $employee = Employee::factory()->create();
        AttendanceRecord::create(['employee_id' => $employee->id, 'work_date' => '2026-10-09', 'status' => 'present']);
        AttendanceRecord::create(['employee_id' => $employee->id, 'work_date' => '2026-10-10', 'status' => 'late']);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/attendance?period=daily&date=2026-10-10&status=late&export=csv');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('late', $content);
        $this->assertStringNotContainsString('2026-10-09', $content);
    }

    public function test_leave_report_lists_requests_by_status(): void
    {
        $employee = Employee::factory()->create();
        $type = LeaveType::create([
            'code' => 'annual', 'name' => 'Cuti Tahunan', 'is_paid' => true,
            'default_quota' => 12, 'tracks_balance' => true, 'requires_attachment' => false, 'is_active' => true,
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $type->id,
            'start_date' => '2026-10-12', 'end_date' => '2026-10-13',
            'total_days' => 2, 'reason' => 'Tes', 'status' => 'approved',
        ]);

        $response = $this->actingAs($this->userWithRole('hrd_admin'))
            ->get('/reports/leaves?status=approved');

        $response->assertOk();
        $response->assertSee($employee->full_name);
    }

    public function test_super_admin_can_view_audit_logs(): void
    {
        $user = $this->userWithRole('super_admin');
        AuditLog::create(['user_id' => $user->id, 'action' => 'leave.approved']);

        $this->actingAs($user)->get('/audit-logs')->assertOk()->assertSee('leave.approved');
    }

    public function test_hrd_without_permission_cannot_view_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'))->get('/audit-logs')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/audit-logs')->assertForbidden();
    }

    public function test_audit_log_records_employee_changes(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $dept = Department::factory()->create();

        $this->actingAs($hrd)->post('/employees', [
            'employee_id' => 'EMP-AUDIT',
            'full_name' => 'Audit Trail',
            'department_id' => $dept->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.created']);
    }

    public function test_audit_log_never_stores_passwords(): void
    {
        $hrd = $this->userWithRole('hrd_admin');
        $dept = Department::factory()->create();

        $this->actingAs($hrd)->post('/employees', [
            'employee_id' => 'EMP-PWD',
            'full_name' => 'Tanpa Password',
            'department_id' => $dept->id,
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'create_account' => true,
            'account_email' => 'pwd@example.com',
            'account_password' => 'super-secret',
            'account_role' => 'karyawan',
        ]);

        foreach (AuditLog::all() as $log) {
            $this->assertStringNotContainsString('super-secret', json_encode($log->new_values));
        }
    }
}
