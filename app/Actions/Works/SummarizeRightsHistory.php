<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\RightsHistory;
use Illuminate\Support\Str;

/**
 * Writes the one-line description of a history entry that the timeline shows.
 */
class SummarizeRightsHistory
{
    public function __invoke(RightsHistory $entry): string
    {
        $name = $entry->new_snapshot['contributor_name']
            ?? $entry->previous_snapshot['contributor_name']
            ?? $entry->workParty->contributor_name
            ?? 'The party';

        return match ($entry->change_type) {
            RightsChangeType::WorkCreated, RightsChangeType::Imported => 'Work created.',
            RightsChangeType::WorkUpdated => $this->describeChanges($entry, 'Work details changed'),
            RightsChangeType::PartyAdded => sprintf(
                '%s added as %s (%s%% performance, %s%% mechanical).',
                $name,
                $entry->new_snapshot['role'] ?? '?',
                $this->plain($entry->new_snapshot['performance_share'] ?? null),
                $this->plain($entry->new_snapshot['mechanical_share'] ?? null),
            ),
            RightsChangeType::PartyRemoved => sprintf('%s (%s) removed.', $name, $entry->previous_snapshot['role'] ?? '?'),
            default => $this->describeChanges($entry, $name),
        };
    }

    /**
     * "Subject: Field from A to B, Field from C to D."
     */
    private function describeChanges(RightsHistory $entry, string $subject): string
    {
        $previous = $entry->previous_snapshot ?? [];
        $new = $entry->new_snapshot ?? [];

        $parts = [];

        foreach (array_keys($new) as $key) {
            if (in_array($key, ['writer_id', 'publisher_id', 'contributor_name'], true)) {
                continue;
            }

            if (json_encode($previous[$key] ?? null) === json_encode($new[$key])) {
                continue;
            }

            $parts[] = sprintf(
                '%s from %s to %s',
                Str::of($key)->replace('_', ' ')->lower(),
                $this->format($key, $previous[$key] ?? null),
                $this->format($key, $new[$key]),
            );
        }

        return $parts === [] ? "{$subject}." : "{$subject}: ".implode(', ', $parts).'.';
    }

    private function format(string $key, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => (string) $item, $value));
        }

        return str_ends_with($key, '_share') ? $this->plain($value).'%' : (string) $value;
    }

    private function plain(mixed $share): string
    {
        return $share === null ? 'N/A' : rtrim(rtrim(number_format((float) $share, 2, '.', ''), '0'), '.');
    }
}
