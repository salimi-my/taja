<?php

namespace App\Http\Controllers;

use App\Actions\Users\DeleteUser;
use App\Http\Requests\Users\BulkDestroyUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserBulkDestroyController extends Controller
{
    /**
     * Delete the selected users, skipping any the current user may not delete.
     */
    public function __invoke(BulkDestroyUserRequest $request, DeleteUser $deleteUser): RedirectResponse
    {
        $deleted = User::query()
            ->whereIn('id', $request->validated('ids'))
            ->get()
            ->filter(fn (User $user): bool => $request->user()->can('delete', $user))
            ->each(fn (User $user) => $deleteUser($user, $request->user()->id))
            ->count();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count user deleted.|:count users deleted.', $deleted),
        ]);

        return back();
    }
}
