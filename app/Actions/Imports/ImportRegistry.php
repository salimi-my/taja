<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportType;

/**
 * Finds the definition of an import type. A type without one is not available yet.
 */
class ImportRegistry
{
    public static function find(CsvImportType $type): ?ImportDefinition
    {
        return match ($type) {
            CsvImportType::Publishers => new PublisherImportDefinition,
            CsvImportType::Writers => new WriterImportDefinition,
            CsvImportType::Works => new WorkImportDefinition,
        };
    }
}
