<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum ConflictStatus: string
{
    use MatchesLooseValues;

    case NoConflict = 'no_conflict';
    case ShareConflict = 'share_conflict';
    case OwnershipConflict = 'ownership_conflict';
    case Duplicate = 'duplicate';
    case SocietyQuery = 'society_query';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::NoConflict => 'No Conflict',
            self::ShareConflict => 'Share Conflict',
            self::OwnershipConflict => 'Ownership Conflict',
            self::Duplicate => 'Duplicate',
            self::SocietyQuery => 'Society Query',
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
