<?php

namespace App\Actions\Users;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\UserChangeType;
use App\Models\User;
use App\Models\UserHistory;

/**
 * Adds one entry to the insert-only change log of a user. Call it inside the
 * transaction that makes the change, so the log and the data never disagree.
 * Record a deletion before deleting the user, so the entry can still refer to
 * the actor when the user deletes their own account.
 */
class RecordUserHistory
{
    /**
     * @param  array<string, mixed>|null  $previous  The state before the change.
     * @param  array<string, mixed>|null  $new  The state after the change.
     */
    public function __invoke(
        User $subject,
        UserChangeType $changeType,
        ?array $previous = null,
        ?array $new = null,
        ?int $actorId = null,
        ?string $remarks = null,
    ): UserHistory {
        return UserHistory::create([
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'subject_email' => $subject->email,
            'change_type' => $changeType,
            'previous_snapshot' => $previous,
            'new_snapshot' => $new,
            'recorded_at' => now(),
            'actor_id' => $actorId,
            'actor_name' => $this->actorName($subject, $actorId),
            'remarks' => filled($remarks) ? $remarks : null,
        ]);
    }

    /**
     * The fields of a user that are safe to log, as stored in a snapshot. It
     * never holds the password, the remember token or the two-factor secrets,
     * and the avatar is only a flag.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => RecordRightsHistory::plain($user->gender),
            'role' => RecordRightsHistory::plain($user->role),
            'has_avatar' => $user->avatar !== null,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }

    /**
     * Only the filled-in values of a snapshot, for the entry that records a new or removed user.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public static function withoutEmptyValues(array $snapshot): array
    {
        return array_filter($snapshot, fn (mixed $value): bool => $value !== null && $value !== []);
    }

    private function actorName(User $subject, ?int $actorId): ?string
    {
        if ($actorId === null) {
            return null;
        }

        return $actorId === $subject->id
            ? $subject->name
            : User::query()->whereKey($actorId)->value('name');
    }
}
