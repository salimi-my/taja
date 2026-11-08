<?php

namespace App\Actions\Imports;

use App\Models\CsvImport;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the first rows of a stored import file, so the preview can show what
 * is about to be imported. A row carries the validation errors found for its
 * line.
 */
class ReadCsvSample
{
    /** The rows shown first, and how many more each "show more" adds. */
    public const ROWS = 10;

    /** The most rows the preview can show. */
    public const MAX_ROWS = 100;

    /**
     * @return array{headers: list<string>, rows: list<array{line: int, data: array<string, string|null>, errors: list<string>, error_columns: list<string>}>}|null Null when the file cannot be read.
     */
    public function __invoke(CsvImport $csvImport, int $limit = self::ROWS): ?array
    {
        $limit = max(self::ROWS, min($limit, self::MAX_ROWS));

        $definition = ImportRegistry::find($csvImport->type);
        $disk = Storage::disk('local');

        if ($definition === null || ! $disk->exists($csvImport->stored_path)) {
            return null;
        }

        try {
            $reader = CsvReader::open($disk->path($csvImport->stored_path), $definition);
        } catch (InvalidImportFile) {
            return null;
        }

        try {
            $rows = [];

            foreach ($reader->chunks($limit) as $chunk) {
                $rows = $chunk;

                break;
            }
        } catch (InvalidImportFile) {
            return null;
        } finally {
            $reader->close();
        }

        $errorsByLine = [];
        $errorColumnsByLine = [];

        foreach ($csvImport->errors ?? [] as $error) {
            $errorsByLine[$error['line']][] = ($error['column'] !== null ? "{$error['column']}: " : '').$error['message'];

            if ($error['column'] !== null) {
                $errorColumnsByLine[$error['line']][$error['column']] = $error['column'];
            }
        }

        return [
            'headers' => $reader->headers,
            'rows' => array_map(fn (array $row): array => [
                ...$row,
                'errors' => $errorsByLine[$row['line']] ?? [],
                'error_columns' => array_values($errorColumnsByLine[$row['line']] ?? []),
            ], $rows),
        ];
    }
}
