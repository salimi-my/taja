<?php

namespace App\Actions\Users;

use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a user and logs a `Created` entry.
 */
class CreateUser
{
    public function __construct(private RecordUserHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The user fields, including the password.
     */
    public function __invoke(array $data, ?int $actorId = null): User
    {
        return DB::transaction(function () use ($data, $actorId): User {
            $user = User::create($data);

            ($this->recordHistory)(
                $user,
                UserChangeType::Created,
                new: RecordUserHistory::withoutEmptyValues(RecordUserHistory::snapshot($user)),
                actorId: $actorId,
            );

            return $user;
        });
    }
}
