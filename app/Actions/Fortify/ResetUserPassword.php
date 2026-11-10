<?php

namespace App\Actions\Fortify;

use App\Actions\Users\RecordUserHistory;
use App\Concerns\PasswordValidationRules;
use App\Enums\UserChangeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private RecordUserHistory $recordHistory) {}

    /**
     * Validate and reset the user's forgotten password. Following the emailed
     * link proves the person owns the address, so the email is verified too.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        DB::transaction(function () use ($user, $input): void {
            $user->forceFill([
                'password' => $input['password'],
            ])->save();

            ($this->recordHistory)($user, UserChangeType::PasswordReset, actorId: $user->id);

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();

                ($this->recordHistory)(
                    $user,
                    UserChangeType::EmailVerified,
                    previous: ['email_verified' => false],
                    new: ['email_verified' => true],
                    actorId: $user->id,
                );
            }
        });
    }
}
