<?php

namespace App\Actions\Masters;

use App\Enums\MasterChangeType;
use App\Models\MasterHistory;
use Illuminate\Support\Str;

/**
 * Writes the one-line description of a master history entry that the
 * timeline and the report show.
 */
class SummarizeMasterHistory
{
    public function __invoke(MasterHistory $entry): string
    {
        $subject = Str::ucfirst($entry->subject_type);

        return match ($entry->change_type) {
            MasterChangeType::Created => "{$subject} created.",
            MasterChangeType::Imported => "{$subject} imported from a CSV file.",
            MasterChangeType::Deleted => "{$subject} deleted.",
            MasterChangeType::Updated => $this->describeChanges($entry, "{$subject} details changed"),
        };
    }

    /**
     * "Subject: field from A to B, field from C to D."
     */
    private function describeChanges(MasterHistory $entry, string $subject): string
    {
        $previous = $entry->previous_snapshot ?? [];
        $new = $entry->new_snapshot ?? [];

        $parts = [];

        foreach (array_keys($new) as $key) {
            $parts[] = sprintf(
                '%s from %s to %s',
                Str::of($key)->replace('_', ' ')->lower(),
                $this->format($previous[$key] ?? null),
                $this->format($new[$key]),
            );
        }

        return $parts === [] ? "{$subject}." : "{$subject}: ".implode(', ', $parts).'.';
    }

    private function format(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => $this->formatItem($item), $value));
        }

        return (string) $value;
    }

    private function formatItem(mixed $item): string
    {
        if (is_array($item)) {
            return trim(($item['name'] ?? '').' '.(filled($item['ipi_base_number'] ?? null) ? "({$item['ipi_base_number']})" : ''));
        }

        return (string) $item;
    }
}
