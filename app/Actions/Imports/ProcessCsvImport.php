<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportStatus;
use App\Models\CsvImport;
use Illuminate\Support\Facades\Storage;

/**
 * One streaming pass over the stored file, run by the queue: either a
 * validation pass (nothing is written, ends as `Previewed`) or an import pass
 * (the valid rows are created, ends as `Imported`). Memory use stays flat:
 * rows are handled a chunk at a time, every row with an error is appended to
 * the errors file, and only the first errors are kept in the database.
 */
class ProcessCsvImport
{
    /**
     * @throws InvalidImportFile When the file is gone or cannot be accepted.
     */
    public function __invoke(CsvImport $csvImport, bool $commit): void
    {
        $definition = ImportRegistry::find($csvImport->type)
            ?? throw new InvalidImportFile('This kind of import is not available yet.');

        $disk = Storage::disk('local');

        if (! $disk->exists($csvImport->stored_path)) {
            throw new InvalidImportFile('The uploaded file is no longer stored. Upload it again.');
        }

        $reader = CsvReader::open($disk->path($csvImport->stored_path), $definition);
        $disk->makeDirectory('imports/errors');
        $disk->delete($csvImport->errorsFilePath());

        $limit = (int) config('imports.max_listed_issues');
        $errors = [];
        $warnings = [];

        foreach ($reader->unknownHeaders as $header) {
            $warnings[] = ['line' => 1, 'column' => $header, 'message' => 'Unknown column, it is ignored.'];
        }

        $warningCount = count($warnings);
        $errorCount = 0;
        $errorRows = 0;
        $totalRows = 0;
        $imported = 0;
        $lastPercent = -1;
        $errorsFile = null;

        $definition->begin($csvImport);

        try {
            foreach (GroupedChunks::wrap($reader->chunks((int) config('imports.chunk_size')), $definition) as $rows) {
                $result = $definition->validateChunk($rows);

                $issuesByLine = [];

                foreach ($result->errors as $error) {
                    $errorCount++;
                    $issuesByLine[$error['line']][] = ($error['column'] !== null ? "{$error['column']}: " : '').$error['message'];

                    if (count($errors) < $limit) {
                        $errors[] = $error;
                    }
                }

                if ($commit) {
                    $committed = $definition->commitChunk($csvImport, $result->records);
                    $imported += $committed['imported'];

                    foreach ($committed['failed'] as $line => $message) {
                        $errorCount++;
                        $issuesByLine[$line][] = $message;

                        if (count($errors) < $limit) {
                            $errors[] = ['line' => $line, 'column' => null, 'message' => $message];
                        }
                    }
                }

                foreach ($result->warnings as $warning) {
                    $warningCount++;

                    if (count($warnings) < $limit) {
                        $warnings[] = $warning;
                    }
                }

                foreach ($rows as $row) {
                    if (! isset($issuesByLine[$row['line']])) {
                        continue;
                    }

                    $errorRows++;
                    $errorsFile ??= $this->openErrorsFile($disk->path($csvImport->errorsFilePath()), $reader->headers);

                    fputcsv($errorsFile, [
                        ...array_map(fn (string $header): string => $row['data'][$header] ?? '', $reader->headers),
                        implode(' | ', $issuesByLine[$row['line']]),
                    ], escape: '');
                }

                $totalRows += count($rows);

                $percent = $reader->percent();

                if ($percent !== $lastPercent) {
                    $lastPercent = $percent;

                    $csvImport->newQuery()->whereKey($csvImport->id)->update([
                        'progress' => min($percent, 99),
                        'total_rows' => $totalRows,
                        'error_rows' => $errorRows,
                        'valid_rows' => $totalRows - $errorRows,
                        'imported_count' => $imported,
                    ]);

                    if ($this->wasCancelled($csvImport)) {
                        $disk->delete($csvImport->errorsFilePath());

                        return;
                    }
                }
            }
        } finally {
            $reader->close();
            $definition->finish($csvImport);

            if ($errorsFile !== null) {
                fclose($errorsFile);
            }
        }

        $csvImport->refresh();

        if ($csvImport->status !== ($commit ? CsvImportStatus::Importing : CsvImportStatus::Validating)) {
            return;
        }

        $csvImport->update([
            'status' => $commit ? CsvImportStatus::Imported : CsvImportStatus::Previewed,
            'progress' => 100,
            'total_rows' => $totalRows,
            'valid_rows' => $totalRows - $errorRows,
            'error_rows' => $errorRows,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'errors' => $errors,
            'warnings' => $warnings,
            'imported_count' => $imported,
            'imported_at' => $commit ? now() : null,
            ...($commit ? [] : ['layout_error' => $definition->layoutError()]),
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @return resource
     */
    private function openErrorsFile(string $path, array $headers)
    {
        $file = fopen($path, 'w') ?: throw new InvalidImportFile('The errors file could not be written.');

        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, [...$headers, 'Error'], escape: '');

        return $file;
    }

    private function wasCancelled(CsvImport $csvImport): bool
    {
        return CsvImport::query()->whereKey($csvImport->id)->value('status') === CsvImportStatus::Cancelled;
    }
}
