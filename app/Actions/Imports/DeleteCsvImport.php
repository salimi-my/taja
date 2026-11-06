<?php

namespace App\Actions\Imports;

use App\Models\CsvImport;
use Illuminate\Support\Facades\Storage;

/**
 * Removes an import from the history together with its uploaded file and its
 * file of skipped rows. Records the import already created are kept, and the
 * rights history entries it wrote lose their link to it.
 */
class DeleteCsvImport
{
    /**
     * @throws InvalidImportFile When a queued job is still working on the file.
     */
    public function __invoke(CsvImport $csvImport): void
    {
        if ($csvImport->status->isProcessing()) {
            throw new InvalidImportFile('This import is still running and cannot be deleted yet.');
        }

        Storage::disk('local')->delete([$csvImport->stored_path, $csvImport->errorsFilePath()]);

        $csvImport->delete();
    }
}
