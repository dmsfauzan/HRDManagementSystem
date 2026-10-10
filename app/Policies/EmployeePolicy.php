<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->can('employees.manage')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->can('employees.manage')) {
            return true;
        }

        $ownEmployee = $user->employee;
        if ($ownEmployee && $ownEmployee->id === $employee->id) {
            return true;
        }

        if ($user->hasRole('manager') && $ownEmployee) {
            return $employee->manager_id === $ownEmployee->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        if (! $user->can('employees.manage')) {
            return false;
        }

        return $user->employee?->id !== $employee->id;
    }

    public function deactivate(User $user, Employee $employee): bool
    {
        if (! $user->can('employees.manage')) {
            return false;
        }

        return $user->employee?->id !== $employee->id;
    }
}
