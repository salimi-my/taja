<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum MasterChangeType: string
{
    use MatchesLooseValues;

    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Imported = 'imported';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Imported => 'Imported',
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
