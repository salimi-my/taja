<?php

namespace App\Actions\Imports;

use Illuminate\Support\Facades\DB;

/**
 * Finds values that appear more than once in a file without keeping every
 * value in memory: a hash of each value is kept in a scratch table. The first
 * row with a value wins, later rows are reported as repeats of it.
 */
final class DuplicateTracker
{
    public function __construct(private readonly int $csvImportId) {}

    /**
     * Remember the values of a chunk and return the repeated ones.
     *
     * @param  array<int, string>  $valuesByLine  The non-empty values of one field, keyed by line.
     * @return array<int, int> The line of the earlier row, keyed by the line of each repeating row.
     */
    public function register(string $field, array $valuesByLine): array
    {
        $repeats = [];
        $firstInChunk = [];

        foreach ($valuesByLine as $line => $value) {
            $hash = md5($value);

            if (isset($firstInChunk[$hash])) {
                $repeats[$line] = $firstInChunk[$hash];

                continue;
            }

            $firstInChunk[$hash] = $line;
        }

        if ($firstInChunk === []) {
            return $repeats;
        }

        $earlier = DB::table('csv_import_values')
            ->where('csv_import_id', $this->csvImportId)
            ->where('field', $field)
            ->whereIn('value_hash', array_keys($firstInChunk))
            ->pluck('line', 'value_hash');

        $new = [];

        foreach ($firstInChunk as $hash => $line) {
            if (isset($earlier[$hash])) {
                $repeats[$line] = (int) $earlier[$hash];

                continue;
            }

            $new[] = [
                'csv_import_id' => $this->csvImportId,
                'field' => $field,
                'value_hash' => $hash,
                'line' => $line,
            ];
        }

        if ($new !== []) {
            DB::table('csv_import_values')->insertOrIgnore($new);
        }

        return $repeats;
    }

    public function clear(): void
    {
        DB::table('csv_import_values')->where('csv_import_id', $this->csvImportId)->delete();
    }
}
