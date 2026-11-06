<?php

namespace App\Enums;

enum CsvImportType: string
{
    case Works = 'works';
    case Writers = 'writers';
    case Publishers = 'publishers';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Works => 'Works',
            self::Writers => 'Writers',
            self::Publishers => 'Publishers',
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
