<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum RightsType: string
{
    use MatchesLooseValues;

    case Performance = 'performance';
    case Mechanical = 'mechanical';
    case Sync = 'sync';
    case Lyrics = 'lyrics';
    case Digital = 'digital';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Performance => 'Performance',
            self::Mechanical => 'Mechanical',
            self::Sync => 'Sync',
            self::Lyrics => 'Lyrics',
            self::Digital => 'Digital',
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
