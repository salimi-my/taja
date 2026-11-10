<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\RecordUserHistory;
use App\Enums\UserChangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileAvatarUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileAvatarController extends Controller
{
    /**
     * Replace the user's avatar with the uploaded image.
     */
    public function update(ProfileAvatarUpdateRequest $request, RecordUserHistory $recordHistory): RedirectResponse
    {
        $user = $request->user();
        $hadAvatar = $user->avatar !== null;

        $this->deleteStoredAvatar($user);

        DB::transaction(function () use ($request, $user, $hadAvatar, $recordHistory): void {
            $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

            $recordHistory($user, UserChangeType::AvatarChanged, ['has_avatar' => $hadAvatar], ['has_avatar' => true], $user->id);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avatar updated.')]);

        return back();
    }

    /**
     * Remove the user's avatar.
     */
    public function destroy(Request $request, RecordUserHistory $recordHistory): RedirectResponse
    {
        $user = $request->user();
        $hadAvatar = $user->avatar !== null;

        $this->deleteStoredAvatar($user);

        DB::transaction(function () use ($user, $hadAvatar, $recordHistory): void {
            $user->update(['avatar' => null]);

            if ($hadAvatar) {
                $recordHistory($user, UserChangeType::AvatarChanged, ['has_avatar' => true], ['has_avatar' => false], $user->id);
            }
        });

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
