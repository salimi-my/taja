<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum UserChangeType: string
{
    use MatchesLooseValues;

    case Created = 'created';
    case Updated = 'updated';
    case RoleChanged = 'role_changed';
    case Deleted = 'deleted';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case AvatarChanged = 'avatar_changed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case EmailVerified = 'email_verified';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::RoleChanged => 'Role changed',
            self::Deleted => 'Deleted',
            self::PasswordChanged => 'Password changed',
            self::PasswordReset => 'Password reset',
            self::AvatarChanged => 'Avatar changed',
            self::TwoFactorEnabled => 'Two-factor enabled',
            self::TwoFactorDisabled => 'Two-factor disabled',
            self::EmailVerified => 'Email verified',
        };
    }

    /**
     * Get every case as a value/label pair, e.g. for select inputs and filters.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
