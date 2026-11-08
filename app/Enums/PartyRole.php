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
    case SA = 'SA';
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
            self::SA => 'Sub-Author',
            self::E => 'Original Publisher',
            self::SE => 'Sub-Publisher',
        };
    }

    /**
     * Get the label with the role code, e.g. "Original Publisher (E)". This is
     * how roles are shown to users, while `label()` stays the plain name that
     * imports match against.
     */
    public function displayLabel(): string
    {
        return "{$this->label()} ({$this->value})";
    }

    /**
     * Whether the role belongs to a writer (composer, author, arranger or sub-author).
     */
    public function isWriterRole(): bool
    {
        return in_array($this, [self::C, self::A, self::CA, self::AR, self::SA], true);
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
     * The label includes the role code, e.g. "Composer (C)".
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->displayLabel()],
            self::cases(),
        );
    }
}
