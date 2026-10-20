<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the list of users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given user. Nobody can delete themselves here.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    /**
     * Determine whether the user can send a password reset link to the given user.
     */
    public function sendPasswordResetLink(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
