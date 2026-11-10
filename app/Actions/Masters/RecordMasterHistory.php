<?php

namespace App\Actions\Masters;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\MasterChangeType;
use App\Models\MasterHistory;
use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Support\Collection;

/**
 * Adds one entry to the insert-only change log of a writer or a publisher.
 * Call it inside the transaction that makes the change, so the log and the
 * data never disagree.
 */
class RecordMasterHistory
{
    /**
     * @param  array<string, mixed>|null  $previous  The state before the change.
     * @param  array<string, mixed>|null  $new  The state after the change.
     */
    public function __invoke(
        Writer|Publisher $subject,
        MasterChangeType $changeType,
        ?array $previous = null,
        ?array $new = null,
        ?int $userId = null,
        ?int $csvImportId = null,
        ?string $remarks = null,
    ): MasterHistory {
        return MasterHistory::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'subject_code' => $subject instanceof Writer ? $subject->writer_code : $subject->publisher_code,
            'subject_name' => $subject->name,
            'change_type' => $changeType,
            'previous_snapshot' => $previous,
            'new_snapshot' => $new,
            'recorded_at' => now(),
            'user_id' => $userId,
            'csv_import_id' => $csvImportId,
            'remarks' => filled($remarks) ? $remarks : null,
        ]);
    }

    /**
     * The code, every field and, for a writer, the publisher's name and the
     * pseudonyms, as stored in a snapshot. Ids are replaced by readable values.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(Writer|Publisher $subject): array
    {
        $snapshot = $subject instanceof Writer
            ? ['writer_code' => $subject->writer_code]
            : ['publisher_code' => $subject->publisher_code];

        foreach ($subject->getFillable() as $attribute) {
            $snapshot[$attribute] = self::plain($subject->getAttribute($attribute));
        }

        if ($subject instanceof Writer) {
            $publisher = $subject->publisher()->first();

            unset($snapshot['publisher_id']);

            $snapshot['publisher'] = $publisher === null ? null : "{$publisher->name} ({$publisher->publisher_code})";

            $snapshot['aliases'] = $subject->aliases()
                ->get(['name', 'ipi_base_number'])
                ->map(fn ($alias): array => ['name' => $alias->name, 'ipi_base_number' => $alias->ipi_base_number])
                ->all();
        }

        return $snapshot;
    }

    /**
     * Only the filled-in values of a snapshot, for the entry that records a new or removed record.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public static function withoutEmptyValues(array $snapshot): array
    {
        return array_filter($snapshot, fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * Turn enums, dates and collections of enums into the plain values a snapshot stores.
     */
    private static function plain(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item): mixed => self::plain($item))->values()->all();
        }

        return RecordRightsHistory::plain($value);
    }
}
