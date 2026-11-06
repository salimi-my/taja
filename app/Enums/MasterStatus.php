<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum MasterStatus: string
{
    use MatchesLooseValues;

    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
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
