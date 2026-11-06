<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\RightsHistory;
use App\Models\Work;
use App\Models\WorkParty;
use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Adds one entry to the insert-only change log of a work. Call it inside the
 * transaction that makes the change, so the log and the data never disagree.
 */
class RecordRightsHistory
{
    /**
     * @param  array<string, mixed>|null  $previous  The state before the change.
     * @param  array<string, mixed>|null  $new  The state after the change.
     */
    public function __invoke(
        Work $work,
        RightsChangeType $changeType,
        CarbonInterface|string $effectiveDate,
        ?WorkParty $party = null,
        ?array $previous = null,
        ?array $new = null,
        ?int $userId = null,
        ?int $csvImportId = null,
        ?string $remarks = null,
    ): RightsHistory {
        return RightsHistory::create([
            'work_id' => $work->id,
            'work_party_id' => $party?->id,
            'change_type' => $changeType,
            'previous_snapshot' => $previous,
            'new_snapshot' => $new,
            'effective_date' => Carbon::parse($effectiveDate)->toDateString(),
            'recorded_at' => now(),
            'user_id' => $userId,
            'csv_import_id' => $csvImportId,
            'remarks' => filled($remarks) ? $remarks : null,
        ]);
    }

    /**
     * The full state of a party row, as stored in a snapshot.
     *
     * @return array<string, mixed>
     */
    public static function partySnapshot(WorkParty $party): array
    {
        return [
            'writer_id' => $party->writer_id,
            'publisher_id' => $party->publisher_id,
            'role' => $party->role->value,
            'contributor_name' => $party->contributor_name,
            'performance_share' => $party->performance_share,
            'mechanical_share' => $party->mechanical_share,
            'sync_share' => $party->sync_share,
            'lyrics_share' => $party->lyrics_share,
            'is_controlled' => $party->is_controlled,
            'territories_controlled' => $party->territories_controlled,
            'territory_exclusions' => $party->territory_exclusions ?? [],
            'effective_start_date' => $party->effective_start_date->toDateString(),
            'effective_end_date' => $party->effective_end_date?->toDateString(),
        ];
    }

    /**
     * The work-level fields and the ISRC list of a work, as stored in a snapshot.
     *
     * @return array<string, mixed>
     */
    public static function workSnapshot(Work $work): array
    {
        $snapshot = ['tap_number' => $work->tap_number];

        foreach ($work->getFillable() as $attribute) {
            $snapshot[$attribute] = self::plain($work->getAttribute($attribute));
        }

        $snapshot['isrcs'] = $work->isrcs()->pluck('isrc')->all();

        return $snapshot;
    }

    /**
     * Turn enums and dates into the plain values a snapshot stores.
     */
    public static function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => Carbon::instance($value)->toDateString(),
            default => $value,
        };
    }

    /**
     * Only the keys whose value differs between two snapshots.
     *
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $new
     * @return array{previous: array<string, mixed>, new: array<string, mixed>}
     */
    public static function changedAttributes(array $previous, array $new): array
    {
        $changed = ['previous' => [], 'new' => []];

        foreach (array_keys($previous + $new) as $key) {
            $before = $previous[$key] ?? null;
            $after = $new[$key] ?? null;

            if (json_encode($before) !== json_encode($after)) {
                $changed['previous'][$key] = $before;
                $changed['new'][$key] = $after;
            }
        }

        return $changed;
    }
}
