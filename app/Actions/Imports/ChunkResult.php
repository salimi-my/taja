<?php

namespace App\Actions\Imports;

/**
 * What validating one chunk of rows found.
 */
final class ChunkResult
{
    /** @var list<array{line: int, column: string|null, message: string}> */
    public array $errors = [];

    /** @var list<array{line: int, column: string|null, message: string}> */
    public array $warnings = [];

    /** @var array<int, mixed> The valid records, keyed by the line they start on. */
    public array $records = [];

    public function addError(int $line, ?string $column, string $message): void
    {
        $this->errors[] = ['line' => $line, 'column' => $column, 'message' => $message];
    }

    public function addWarning(int $line, ?string $column, string $message): void
    {
        $this->warnings[] = ['line' => $line, 'column' => $column, 'message' => $message];
    }

    public function addRecord(int $line, mixed $record): void
    {
        $this->records[$line] = $record;
    }
}
