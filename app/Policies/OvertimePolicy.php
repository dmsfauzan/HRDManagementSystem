<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;

class OvertimePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee || $user->can('attendance.manage') || $user->hasRole('manager');
    }

    public function view(User $user, Overtime $overtime): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        if ($user->employee && $user->employee->id === $overtime->employee_id) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $overtime->employee?->manager_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->employee;
    }

    public function decide(User $user, Overtime $overtime): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $overtime->employee?->manager_id === $user->employee->id;
    }

    public function cancel(User $user, Overtime $overtime): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        return $user->employee
            && $user->employee->id === $overtime->employee_id
            && in_array($overtime->status, [Overtime::STATUS_PENDING, Overtime::STATUS_APPROVED], true);
    }
}
