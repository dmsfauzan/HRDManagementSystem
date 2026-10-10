<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->employee) {
            return true;
        }

        return (bool) $user->employee;
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if ($user->can('attendance.manage')) {
            return true;
        }

        if ($user->employee && $user->employee->id === $record->employee_id) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $record->employee?->manager_id === $user->employee->id;
    }

    public function checkIn(User $user, ?Employee $employee = null): bool
    {
        if (! $user->employee || ! $user->employee->isActive()) {
            return false;
        }

        if ($user->can('attendance.manage')) {
            return true;
        }

        return $employee === null || $employee->id === $user->employee->id;
    }

    public function checkOut(User $user, ?Employee $employee = null): bool
    {
        return $this->checkIn($user, $employee);
    }
}
