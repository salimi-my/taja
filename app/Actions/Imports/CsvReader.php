<?php

namespace App\Actions\Imports;

use Generator;

/**
 * Reads an import file as a stream with native CSV functions, so memory use
 * stays flat however large the file is. A UTF-8 BOM is tolerated, headers are
 * trimmed and matched ignoring case, blank lines are skipped. The header row
 * is checked when the file is opened.
 */
final class CsvReader
{
    /** @var resource */
    private $handle;

    /** @var list<string>|null The first data row, read ahead to prove the file has data. */
    private ?array $pendingRecord = null;

    private int $pendingLine = 0;

    private int $fileSize;

    /**
     * @param  resource  $handle
     * @param  list<string>  $headers  The known headers present in the file, in file order.
     * @param  list<string>  $unknownHeaders  Headers that match no column; they are ignored.
     * @param  array<int, string>  $columnByIndex
     */
    private function __construct(
        $handle,
        public readonly array $headers,
        public readonly array $unknownHeaders,
        private readonly array $columnByIndex,
        int $fileSize,
    ) {
        $this->handle = $handle;
        $this->fileSize = $fileSize;
    }

    /**
     * @throws InvalidImportFile When the whole file has to be rejected.
     */
    public static function open(string $path, ImportDefinition $definition): self
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidImportFile('The file could not be read.');
        }

        try {
            [$headers, $unknown, $columnByIndex] = self::readHeader($handle, $definition);

            $reader = new self($handle, $headers, $unknown, $columnByIndex, (int) filesize($path));
            $reader->readAhead();
        } catch (InvalidImportFile $exception) {
            fclose($handle);

            throw $exception;
        }

        return $reader;
    }

    /**
     * Yield the data rows in chunks, each row with its line number (the header is line 1).
     *
     * @return Generator<int, list<array{line: int, data: array<string, string|null>}>>
     *
     * @throws InvalidImportFile When the file has more rows than allowed.
     */
    public function chunks(int $size): Generator
    {
        $maxRows = (int) config('imports.max_rows');
        $chunk = [];
        $count = 0;

        foreach ($this->records() as $line => $record) {
            $data = $this->toRow($record, $line);

            if (array_filter($data, fn (?string $value): bool => $value !== null) === []) {
                continue;
            }

            if (++$count > $maxRows) {
                throw new InvalidImportFile('The file has more than '.number_format($maxRows).' data rows. Split it into smaller files.');
            }

            $chunk[] = ['line' => $line, 'data' => $data];

            if (count($chunk) >= $size) {
                yield $chunk;

                $chunk = [];
            }
        }

        if ($chunk !== []) {
            yield $chunk;
        }
    }

    /**
     * How much of the file has been read, from 0 to 100.
     */
    public function percent(): int
    {
        if ($this->fileSize === 0) {
            return 100;
        }

        return min(100, (int) floor(ftell($this->handle) / $this->fileSize * 100));
    }

    public function close(): void
    {
        fclose($this->handle);
    }

    /**
     * @return Generator<int, list<string|null>>
     */
    private function records(): Generator
    {
        $line = $this->pendingLine;
        $record = $this->pendingRecord;
        $this->pendingRecord = null;

        while ($record !== null) {
            yield $line => $record;

            do {
                $line++;
                $record = fgetcsv($this->handle, escape: '');
            } while ($record === [null]);

            if ($record === false) {
                return;
            }
        }
    }

    /**
     * Read the first data row, so a file with a header and nothing else is rejected at once.
     */
    private function readAhead(): void
    {
        $line = 1;

        do {
            $line++;
            $record = fgetcsv($this->handle, escape: '');
        } while ($record === [null]);

        if ($record === false) {
            throw new InvalidImportFile('The file has no data rows.');
        }

        $this->pendingRecord = $record;
        $this->pendingLine = $line;
    }

    /**
     * @param  list<string|null>  $record
     * @return array<string, string|null>
     */
    private function toRow(array $record, int $line): array
    {
        $data = [];

        foreach ($this->columnByIndex as $index => $header) {
            $value = trim((string) ($record[$index] ?? ''));

            if (! mb_check_encoding($value, 'UTF-8')) {
                throw new InvalidImportFile("The file must be UTF-8 encoded (see line {$line}).");
            }

            $data[$header] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * @param  resource  $handle
     * @return array{0: list<string>, 1: list<string>, 2: array<int, string>}
     */
    private static function readHeader($handle, ImportDefinition $definition): array
    {
        $record = fgetcsv($handle, escape: '');

        if ($record === false || $record === [null]) {
            throw new InvalidImportFile('The file is empty. The first row must contain the column headers.');
        }

        $record[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $record[0]);

        $columnByIndex = [];
        $unknown = [];

        foreach ($record as $index => $cell) {
            $header = trim((string) $cell);

            if ($header === '') {
                continue;
            }

            $column = $definition->findColumn($header);

            if ($column === null) {
                $unknown[] = $header;

                continue;
            }

            if (in_array($column->header, $columnByIndex, true)) {
                throw new InvalidImportFile("The column {$column->header} appears more than once.");
            }

            $columnByIndex[$index] = $column->header;
        }

        $missing = array_values(array_diff($definition->requiredHeaders(), $columnByIndex));

        if ($missing !== []) {
            throw new InvalidImportFile('The file is missing required column(s): '.implode(', ', $missing).'.');
        }

        return [array_values($columnByIndex), $unknown, $columnByIndex];
    }
}
