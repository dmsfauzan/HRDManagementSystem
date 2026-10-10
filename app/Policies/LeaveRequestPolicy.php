<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->employee || $user->can('leave.manage');
    }

    public function view(User $user, LeaveRequest $request): bool
    {
        if ($user->can('leave.manage')) {
            return true;
        }

        if ($user->employee && $user->employee->id === $request->employee_id) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $request->employee?->manager_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->employee;
    }

    public function decide(User $user, LeaveRequest $request): bool
    {
        $canDecide = $user->can('leave.manage') || $user->can('leave.approve');
        if (! $canDecide) {
            return false;
        }

        if ($user->can('leave.manage')) {
            return true;
        }

        return $user->hasRole('manager') && $user->employee
            && $request->employee?->manager_id === $user->employee->id;
    }

    public function cancel(User $user, LeaveRequest $request): bool
    {
        if ($user->can('leave.manage')) {
            return true;
        }

        return $user->employee
            && $user->employee->id === $request->employee_id
            && in_array($request->status, ['pending', 'approved'], true);
    }
}
