<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum RegistrationStatus: string
{
    use MatchesLooseValues;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Conflict = 'conflict';
    case Amended = 'amended';
    case Relinquished = 'relinquished';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Conflict => 'Conflict',
            self::Amended => 'Amended',
            self::Relinquished => 'Relinquished',
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
