<?php

namespace App\Listeners;

use App\Actions\Users\RecordUserHistory;
use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Features;

/**
 * Logs when a user turns two-factor authentication on or off. When Fortify
 * requires a confirmation code, it counts as enabled once the code is
 * confirmed, so the unconfirmed first step is not logged.
 */
class RecordTwoFactorHistory
{
    public function __construct(private RecordUserHistory $recordHistory) {}

    public function handle(TwoFactorAuthenticationEnabled|TwoFactorAuthenticationConfirmed|TwoFactorAuthenticationDisabled $event): void
    {
        if ($event instanceof TwoFactorAuthenticationEnabled && $this->requiresConfirmation()) {
            return;
        }

        /** @var User $user */
        $user = $event->user;

        ($this->recordHistory)(
            $user,
            $event instanceof TwoFactorAuthenticationDisabled ? UserChangeType::TwoFactorDisabled : UserChangeType::TwoFactorEnabled,
            actorId: is_numeric($actorId = Auth::id()) ? (int) $actorId : $user->id,
        );
    }

    private function requiresConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }
}
