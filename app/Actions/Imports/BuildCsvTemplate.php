<?php

namespace App\Actions\Imports;

use RuntimeException;

/**
 * Builds the downloadable template: the header row, optionally followed by
 * the fake example rows. A UTF-8 BOM is added so Excel reads it correctly.
 */
class BuildCsvTemplate
{
    public function __invoke(ImportDefinition $definition, bool $withExample = false): string
    {
        $headers = $definition->headers();
        $lines = [$headers];

        if ($withExample) {
            foreach ($definition->exampleRows() as $row) {
                $lines[] = array_map(fn (string $header): string => $row[$header] ?? '', $headers);
            }
        }

        return "\xEF\xBB\xBF".self::toCsv($lines);
    }

    /**
     * @param  list<list<string>>  $lines
     */
    public static function toCsv(array $lines): string
    {
        $stream = fopen('php://temp', 'r+') ?: throw new RuntimeException('Could not open a temporary stream.');

        foreach ($lines as $line) {
            fputcsv($stream, $line, escape: '');
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return (string) $content;
    }
}
