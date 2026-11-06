<?php

namespace App\Policies;

use App\Models\Publisher;
use App\Models\User;

class PublisherPolicy
{
    /**
     * Determine whether the user can view the list of publishers.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given publisher.
     */
    public function view(User $user, Publisher $publisher): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create publishers.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given publisher.
     */
    public function update(User $user, Publisher $publisher): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given publisher.
     */
    public function delete(User $user, Publisher $publisher): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the given publisher.
     */
    public function restore(User $user, Publisher $publisher): bool
    {
        return $user->isAdmin();
    }
}
