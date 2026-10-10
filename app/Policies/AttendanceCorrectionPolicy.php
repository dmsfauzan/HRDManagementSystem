<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee || $user->can('attendance.manage') || $user->hasRole('manager');
    }

    public function view(User $user, AttendanceCorrection $correction): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        if ($user->employee && $user->employee->id === $correction->employee_id) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $correction->employee?->manager_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->employee;
    }

    public function decide(User $user, AttendanceCorrection $correction): bool
    {
        if (! $user->can('attendance.manage') && ! $user->can('attendance.correction_approve')) {
            return false;
        }

        if ($user->can('attendance.manage')) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $correction->employee?->manager_id === $user->employee->id;
    }
}
