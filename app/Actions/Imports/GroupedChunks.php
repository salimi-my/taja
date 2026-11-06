<?php

namespace App\Actions\Imports;

use Generator;

/**
 * Re-cuts the chunks of a file so that rows sharing a group key (see
 * `ImportDefinition::groupKey`) are never split over two chunks: the trailing
 * group of a chunk is held back and put in front of the next chunk.
 */
final class GroupedChunks
{
    /**
     * @param  iterable<int, list<array{line: int, data: array<string, string|null>}>>  $chunks
     * @return Generator<int, list<array{line: int, data: array<string, string|null>}>>
     */
    public static function wrap(iterable $chunks, ImportDefinition $definition): Generator
    {
        $carried = [];

        foreach ($chunks as $chunk) {
            $rows = [...$carried, ...$chunk];
            $keepFrom = self::trailingGroupStart($rows, $definition);

            $carried = array_slice($rows, $keepFrom);
            $rows = array_slice($rows, 0, $keepFrom);

            if ($rows !== []) {
                yield $rows;
            }
        }

        if ($carried !== []) {
            yield $carried;
        }
    }

    /**
     * The index where the trailing group of rows starts, or the row count when
     * the last row stands alone.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     */
    private static function trailingGroupStart(array $rows, ImportDefinition $definition): int
    {
        $last = count($rows) - 1;
        $key = $definition->groupKey($rows[$last]['data']);

        if ($key === null) {
            return count($rows);
        }

        $start = $last;

        while ($start > 0 && $definition->groupKey($rows[$start - 1]['data']) === $key) {
            $start--;
        }

        return $start;
    }
}
