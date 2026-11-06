<?php

namespace App\Actions\Imports;

use App\Enums\CsvImportType;
use App\Models\CsvImport;

/**
 * Everything one kind of import needs: its columns, an example, how to
 * validate chunks of rows and how to create the records.
 */
abstract class ImportDefinition
{
    abstract public function type(): CsvImportType;

    /**
     * @return list<ImportColumn>
     */
    abstract public function columns(): array;

    /**
     * Get ready for a pass over the file (a validation or an import pass).
     */
    public function begin(CsvImport $import): void {}

    /**
     * Clean up after a pass, also when it failed.
     */
    public function finish(CsvImport $import): void {}

    /**
     * The key that ties neighbouring rows to one record, or null when every
     * row stands alone. Rows with the same key that follow each other are
     * always handed over in the same chunk, so a record is never split.
     *
     * @param  array<string, string|null>  $data
     */
    public function groupKey(array $data): ?string
    {
        return null;
    }

    /**
     * A problem with how the whole file is laid out, found by the validation
     * pass. While there is one, the file cannot be confirmed.
     */
    public function layoutError(): ?string
    {
        return null;
    }

    /**
     * Validate one chunk of rows. Nothing is written.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     */
    abstract public function validateChunk(array $rows): ChunkResult;

    /**
     * Create the valid records of a chunk.
     *
     * @param  array<int, mixed>  $records  The records of the chunk result, keyed by line.
     * @return array{imported: int, failed: array<int, string>} How many were created, and the reason each failed line was not.
     */
    abstract public function commitChunk(CsvImport $import, array $records): array;

    /**
     * Fake example rows, keyed by header. By default one row built from the column examples.
     *
     * @return list<array<string, string>>
     */
    public function exampleRows(): array
    {
        return [$this->exampleRow()];
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return array_map(fn (ImportColumn $column): string => $column->header, $this->columns());
    }

    /**
     * @return list<string>
     */
    public function requiredHeaders(): array
    {
        return array_values(array_map(
            fn (ImportColumn $column): string => $column->header,
            array_filter($this->columns(), fn (ImportColumn $column): bool => $column->required),
        ));
    }

    /**
     * Find a column by its header, ignoring case.
     */
    public function findColumn(string $header): ?ImportColumn
    {
        foreach ($this->columns() as $column) {
            if (strcasecmp($column->header, trim($header)) === 0) {
                return $column;
            }
        }

        return null;
    }

    /**
     * A row of the column examples, optionally with some values replaced.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    protected function exampleRow(array $overrides = []): array
    {
        $row = [];

        foreach ($this->columns() as $column) {
            $row[$column->header] = $column->example;
        }

        return [...$row, ...$overrides];
    }
}
