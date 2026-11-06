<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportStatus;
use App\Models\CsvImport;
use Illuminate\Support\Facades\Storage;

/**
 * Drops an import that is being validated or waits for confirmation, together
 * with its files, without importing anything. A running validation notices
 * the cancellation and stops.
 */
class CancelCsvImport
{
    public function __invoke(CsvImport $csvImport): CsvImport
    {
        $cancelled = CsvImport::query()
            ->whereKey($csvImport->id)
            ->whereIn('status', [CsvImportStatus::Validating, CsvImportStatus::Previewed])
            ->update(['status' => CsvImportStatus::Cancelled]);

        if ($cancelled === 0) {
            throw new InvalidImportFile('This import can no longer be cancelled.');
        }

        Storage::disk('local')->delete([$csvImport->stored_path, $csvImport->errorsFilePath()]);

        return $csvImport->refresh();
    }
}
