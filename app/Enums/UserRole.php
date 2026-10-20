<?php

namespace App\Enums;

enum UserRole: string
{
    /**
     * The first case is the default role preselected when creating a user, so
     * keep the least privileged role first.
     */
    case User = 'user';
    case Admin = 'admin';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Admin',
        };
    }

    /**
     * Get every role as a value/label pair, e.g. for select inputs and filters.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
