<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public const ROLES = ['super_admin', 'hrd_admin', 'manager', 'karyawan'];

    public const PERMISSIONS = [
        'users.manage',
        'roles.manage',
        'system.settings',
        'audit_logs.view',
        'employees.manage',
        'employees.view',
        'departments.manage',
        'positions.manage',
        'attendance.manage',
        'attendance.view_own',
        'attendance.view_team',
        'attendance.correction_approve',
        'leave.manage',
        'leave.view_own',
        'leave.approve',
        'leave.balances_manage',
        'reports.view',
        'documents.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'super_admin' => self::PERMISSIONS,
            'hrd_admin' => [
                'employees.manage', 'employees.view',
                'departments.manage', 'positions.manage',
                'attendance.manage', 'attendance.view_own',
                'leave.manage', 'leave.view_own', 'leave.approve', 'leave.balances_manage',
                'reports.view', 'documents.manage',
            ],
            'manager' => [
                'employees.view', 'attendance.view_team', 'attendance.correction_approve',
                'leave.view_own', 'leave.approve',
            ],
            'karyawan' => [
                'employees.view', 'attendance.view_own', 'leave.view_own',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
