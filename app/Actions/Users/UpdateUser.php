<?php

namespace App\Actions\Users;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Updates a user, then logs an `Updated` entry holding what changed, or a
 * `RoleChanged` entry when the role is all that changed. Changing the email
 * address makes it unverified again. Nothing is logged when nothing changed.
 */
class UpdateUser
{
    public function __construct(private RecordUserHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The user fields to change.
     */
    public function __invoke(User $user, array $data, ?int $actorId = null): User
    {
        return DB::transaction(function () use ($user, $data, $actorId): User {
            $previous = RecordUserHistory::snapshot($user);

            $user->fill($data);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $changes = RecordRightsHistory::changedAttributes($previous, RecordUserHistory::snapshot($user));

            if ($changes['new'] !== []) {
                ($this->recordHistory)(
                    $user,
                    array_keys($changes['new']) === ['role'] ? UserChangeType::RoleChanged : UserChangeType::Updated,
                    previous: $changes['previous'],
                    new: $changes['new'],
                    actorId: $actorId,
                );
            }

            return $user;
        });
    }
}
