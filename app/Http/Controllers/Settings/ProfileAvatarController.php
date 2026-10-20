<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileAvatarUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileAvatarController extends Controller
{
    /**
     * Replace the user's avatar with the uploaded image.
     */
    public function update(ProfileAvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->deleteStoredAvatar($user);

        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avatar updated.')]);

        return back();
    }

    /**
     * Remove the user's avatar.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->deleteStoredAvatar($user);

        $user->update(['avatar' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avatar removed.')]);

        return back();
    }

    /**
     * Delete the user's current avatar file from the public disk.
     */
    private function deleteStoredAvatar(User $user): void
    {
        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
