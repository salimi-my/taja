<?php

namespace App\Actions\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a report as a CSV download. A UTF-8 BOM is added so Excel reads it
 * correctly, and cells that a spreadsheet could run as a formula are prefixed
 * with a single quote.
 */
class StreamCsv
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<list<scalar|null>>  $rows
     */
    public function __invoke(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, escape: '');

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->sanitize(...), $row), escape: '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  scalar|null  $value
     */
    private function sanitize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Y' : 'N';
        }

        $text = (string) $value;

        if ($text !== '' && str_contains("=+@\t\r", $text[0])) {
            return "'".$text;
        }

        if (str_starts_with($text, '-') && ! is_numeric($text)) {
            return "'".$text;
        }

        return $text;
    }
}
