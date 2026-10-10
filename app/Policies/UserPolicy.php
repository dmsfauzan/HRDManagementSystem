<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return $user->id !== $model->id;
    }

    public function toggleActive(User $user, User $model): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return $user->id !== $model->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return $user->id !== $model->id;
    }
}
