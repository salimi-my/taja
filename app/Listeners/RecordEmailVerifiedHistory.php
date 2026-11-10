<?php

namespace App\Listeners;

use App\Actions\Users\RecordUserHistory;
use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Logs when a user verifies their email address from the emailed link.
 */
class RecordEmailVerifiedHistory
{
    public function __construct(private RecordUserHistory $recordHistory) {}

    public function handle(Verified $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        ($this->recordHistory)(
            $event->user,
            UserChangeType::EmailVerified,
            previous: ['email_verified' => false],
            new: ['email_verified' => true],
            actorId: $event->user->id,
        );
    }
}
