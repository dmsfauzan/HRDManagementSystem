<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) now()->format('Y');
        $schedule = WorkSchedule::where('code', 'REG')->first();
        $deptHrd = Department::where('code', 'HRD')->first();
        $posHrdMgr = Position::where('code', 'HRD-MGR')->first();
        $deptIt = Department::where('code', 'IT')->first();
        $posItMgr = Position::where('code', 'IT-MGR')->first();
        $posItDev = Position::where('code', 'IT-DEV')->first();

        $accounts = [
            [
                'name' => 'Super Administrator', 'email' => 'superadmin@hris.test', 'role' => 'super_admin',
                'employee' => null,
            ],
            [
                'name' => 'Siti Rahayu', 'email' => 'hrd@hris.test', 'role' => 'hrd_admin',
                'employee' => [
                    'employee_id' => 'HRD-001', 'full_name' => 'Siti Rahayu',
                    'department_id' => $deptHrd?->id, 'position_id' => $posHrdMgr?->id,
                    'join_date' => '2023-01-10', 'employment_type' => 'permanent',
                ],
            ],
            [
                'name' => 'Budi Santoso', 'email' => 'manager@hris.test', 'role' => 'manager',
                'employee' => [
                    'employee_id' => 'IT-001', 'full_name' => 'Budi Santoso',
                    'department_id' => $deptIt?->id, 'position_id' => $posItMgr?->id,
                    'join_date' => '2023-03-01', 'employment_type' => 'permanent',
                ],
            ],
            [
                'name' => 'Andi Wijaya', 'email' => 'karyawan@hris.test', 'role' => 'karyawan',
                'employee' => [
                    'employee_id' => 'IT-002', 'full_name' => 'Andi Wijaya',
                    'department_id' => $deptIt?->id, 'position_id' => $posItDev?->id,
                    'join_date' => '2024-06-03', 'employment_type' => 'permanent',
                ],
            ],
        ];

        $managerEmployee = null;

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);

            $avatar = sprintf(
                'https://ui-avatars.com/api/?name=%s&background=059669&color=fff&bold=true',
                urlencode($account['name'])
            );

            if ($account['employee']) {
                $employeeData = $account['employee'] + [
                    'user_id' => $user->id,
                    'work_schedule_id' => $schedule?->id,
                    'employment_status' => 'active',
                    'phone' => '0812'.random_int(1000000, 9999999),
                    'photo_path' => null,
                ];

                if ($account['role'] === 'karyawan') {
                    $employeeData['manager_id'] = $managerEmployee?->id;
                }

                $employee = Employee::updateOrCreate(
                    ['employee_id' => $employeeData['employee_id']],
                    $employeeData
                );

                if ($account['role'] === 'manager') {
                    $managerEmployee = $employee;
                }

                $annual = LeaveType::where('code', 'annual')->first();
                if ($annual) {
                    LeaveBalance::updateOrCreate(
                        ['employee_id' => $employee->id, 'leave_type_id' => $annual->id, 'year' => $year],
                        ['balance' => $annual->default_quota, 'used' => 0]
                    );
                }
            }
        }
    }
}
