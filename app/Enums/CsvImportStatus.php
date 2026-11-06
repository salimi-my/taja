<?php

namespace App\Enums;

enum CsvImportStatus: string
{
    case Validating = 'validating';
    case Previewed = 'previewed';
    case Importing = 'importing';
    case Imported = 'imported';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::Validating => 'Validating',
            self::Previewed => 'Previewed',
            self::Importing => 'Importing',
            self::Imported => 'Imported',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }

    /**
     * Whether a queued job is still working on the file.
     */
    public function isProcessing(): bool
    {
        return $this === self::Validating || $this === self::Importing;
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
