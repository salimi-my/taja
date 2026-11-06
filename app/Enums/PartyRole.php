<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum PartyRole: string
{
    use MatchesLooseValues;

    case C = 'C';
    case A = 'A';
    case CA = 'CA';
    case AR = 'AR';
    case E = 'E';
    case SE = 'SE';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::C => 'Composer',
            self::A => 'Author/Lyricist',
            self::CA => 'Composer/Author',
            self::AR => 'Arranger',
            self::E => 'Original Publisher',
            self::SE => 'Sub-Publisher',
        };
    }

    /**
     * Whether the role belongs to a writer (composer, author or arranger).
     */
    public function isWriterRole(): bool
    {
        return in_array($this, [self::C, self::A, self::CA, self::AR], true);
    }

    /**
     * Whether the role belongs to a publisher (original or sub-publisher).
     */
    public function isPublisherRole(): bool
    {
        return in_array($this, [self::E, self::SE], true);
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
