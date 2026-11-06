<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;

class WorkPolicy
{
    /**
     * Determine whether the user can view the list of works.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given work.
     */
    public function view(User $user, Work $work): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create works.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given work.
     */
    public function update(User $user, Work $work): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given work.
     */
    public function delete(User $user, Work $work): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the given work.
     */
    public function restore(User $user, Work $work): bool
    {
        return $user->isAdmin();
    }
}
