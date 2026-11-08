<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    /**
     * Get the human-readable label for the gender.
     */
    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Other => 'Other',
        };
    }

    /**
     * Get every gender as a value/label pair, e.g. for radio inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $gender): array => ['value' => $gender->value, 'label' => $gender->label()],
            self::cases(),
        );
    }
}
