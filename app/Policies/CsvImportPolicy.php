<?php

namespace App\Policies;

use App\Models\CsvImport;
use App\Models\User;

/**
 * Only admins import data: regular users are read-only.
 */
class CsvImportPolicy
{
    /**
     * Determine whether the user can open the import pages and download templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the preview of the given import.
     */
    public function view(User $user, CsvImport $csvImport): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can upload a CSV file.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can confirm or cancel the given import.
     */
    public function update(User $user, CsvImport $csvImport): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given import. One that is
     * still being processed has to finish or be cancelled first.
     */
    public function delete(User $user, CsvImport $csvImport): bool
    {
        return $user->isAdmin() && ! $csvImport->status->isProcessing();
    }
}
