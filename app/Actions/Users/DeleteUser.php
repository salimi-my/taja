<?php

namespace App\Actions\Users;

use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a user and logs a `Deleted` entry holding its last state. The entry
 * is written first, so it can still point at the actor when the actor is the
 * user being deleted (the database then clears the actor link, and the copied
 * actor name stays).
 */
class DeleteUser
{
    public function __construct(private RecordUserHistory $recordHistory) {}

    public function __invoke(User $user, ?int $actorId = null): void
    {
        DB::transaction(function () use ($user, $actorId): void {
            ($this->recordHistory)(
                $user,
                UserChangeType::Deleted,
                previous: RecordUserHistory::withoutEmptyValues(RecordUserHistory::snapshot($user)),
                actorId: $actorId,
            );

            $user->delete();
        });
    }
}
