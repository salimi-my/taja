<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportStatus;
use App\Jobs\ProcessCsvImportJob;
use App\Models\CsvImport;
use Illuminate\Support\Facades\Storage;

/**
 * Starts importing the valid rows of a previewed file. The queued job reads
 * the stored file again and validates it again, so the browser is never
 * trusted and a row that became invalid since the preview is skipped and
 * reported.
 */
class ConfirmCsvImport
{
    /**
     * @throws InvalidImportFile When the stored file is gone or the import is not waiting for confirmation.
     */
    public function __invoke(CsvImport $csvImport): CsvImport
    {
        if (ImportRegistry::find($csvImport->type) === null) {
            throw new InvalidImportFile('This kind of import is not available yet.');
        }

        if (! Storage::disk('local')->exists($csvImport->stored_path)) {
            throw new InvalidImportFile('The uploaded file is no longer stored. Upload it again.');
        }

        if ($csvImport->layout_error !== null) {
            throw new InvalidImportFile('Fix the layout of the file and upload it again.');
        }

        $claimed = CsvImport::query()
            ->whereKey($csvImport->id)
            ->where('status', CsvImportStatus::Previewed)
            ->update(['status' => CsvImportStatus::Importing, 'progress' => 0, 'imported_count' => 0]);

        if ($claimed === 0) {
            throw new InvalidImportFile('This import was already confirmed or cancelled.');
        }

        $csvImport->refresh();

        ProcessCsvImportJob::dispatch($csvImport, commit: true);

        return $csvImport;
    }
}
