<?php

namespace App\Actions\Users;

use App\Enums\UserChangeType;
use App\Models\UserHistory;
use Illuminate\Support\Str;

/**
 * Writes the one-line description of a user history entry that the report
 * shows. Events about secrets (password, two-factor) never show values.
 */
class SummarizeUserHistory
{
    public function __invoke(UserHistory $entry): string
    {
        return match ($entry->change_type) {
            UserChangeType::Created => 'User created.',
            UserChangeType::Deleted => 'User deleted.',
            UserChangeType::Updated => $this->describeChanges($entry, 'User details changed'),
            UserChangeType::RoleChanged => $this->describeChanges($entry, 'User role changed'),
            UserChangeType::PasswordChanged => 'Password changed.',
            UserChangeType::PasswordReset => 'Password reset with an emailed link.',
            UserChangeType::AvatarChanged => ($entry->new_snapshot['has_avatar'] ?? false) ? 'Avatar uploaded.' : 'Avatar removed.',
            UserChangeType::TwoFactorEnabled => 'Two-factor authentication enabled.',
            UserChangeType::TwoFactorDisabled => 'Two-factor authentication disabled.',
            UserChangeType::EmailVerified => 'Email address verified.',
        };
    }

    /**
     * "Subject: field from A to B, field from C to D."
     */
    private function describeChanges(UserHistory $entry, string $subject): string
    {
        $previous = $entry->previous_snapshot ?? [];
        $new = $entry->new_snapshot ?? [];

        $parts = [];

        foreach (array_keys($new) as $key) {
            $parts[] = sprintf(
                '%s from %s to %s',
                Str::of($key)->replace('_', ' ')->lower(),
                $this->format($previous[$key] ?? null),
                $this->format($new[$key]),
            );
        }

        return $parts === [] ? "{$subject}." : "{$subject}: ".implode(', ', $parts).'.';
    }

    private function format(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }
}
