<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Writer;

class WriterPolicy
{
    /**
     * Determine whether the user can view the list of writers.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given writer.
     */
    public function view(User $user, Writer $writer): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create writers.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given writer.
     */
    public function update(User $user, Writer $writer): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given writer.
     */
    public function delete(User $user, Writer $writer): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the given writer.
     */
    public function restore(User $user, Writer $writer): bool
    {
        return $user->isAdmin();
    }
}
