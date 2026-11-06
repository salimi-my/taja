<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportStatus;
use App\Jobs\ProcessCsvImportJob;
use App\Models\CsvImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Stores an uploaded file, checks its header row at once and queues the
 * validation of the rows. No master data is written until the import is
 * confirmed.
 */
class PreviewCsvImport
{
    /**
     * @throws ValidationException When the whole file is rejected.
     */
    public function __invoke(UploadedFile $file, ImportDefinition $definition, User $user): CsvImport
    {
        $disk = Storage::disk('local');
        $path = $disk->putFile('imports', $file);

        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'The file could not be stored. Try again.']);
        }

        try {
            CsvReader::open($disk->path($path), $definition)->close();
        } catch (InvalidImportFile $exception) {
            $disk->delete($path);

            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $csvImport = CsvImport::create([
            'user_id' => $user->id,
            'type' => $definition->type(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => CsvImportStatus::Validating,
        ]);

        ProcessCsvImportJob::dispatch($csvImport, commit: false);

        return $csvImport;
    }
}
