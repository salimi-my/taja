<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\Work;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Updates the work-level fields and replaces the ISRC list, then logs a
 * `WorkUpdated` entry holding what changed. Parties are never touched here:
 * rights data is not overwritten (business rule 1).
 */
class UpdateWork
{
    public function __construct(private RecordRightsHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The work fields, plus optional `isrcs`, `effective_date` (defaults to today) and `remarks`.
     */
    public function __invoke(Work $work, array $data, ?int $userId = null): Work
    {
        return DB::transaction(function () use ($work, $data, $userId): Work {
            $previous = RecordRightsHistory::workSnapshot($work);

            $work->update(Arr::except($data, ['isrcs', 'parties', 'effective_date', 'remarks']));

            if (array_key_exists('isrcs', $data)) {
                $this->syncIsrcs($work, $data['isrcs'] ?? []);
            }

            $changes = RecordRightsHistory::changedAttributes($previous, RecordRightsHistory::workSnapshot($work->refresh()));

            if ($changes['new'] !== []) {
                ($this->recordHistory)(
                    $work,
                    RightsChangeType::WorkUpdated,
                    $data['effective_date'] ?? CarbonImmutable::today(),
                    previous: $changes['previous'],
                    new: $changes['new'],
                    userId: $userId,
                    remarks: $data['remarks'] ?? null,
                );
            }

            return $work;
        });
    }

    /**
     * Keep the ISRCs that are still listed, add new ones and remove the rest.
     *
     * @param  list<string>  $isrcs
     */
    private function syncIsrcs(Work $work, array $isrcs): void
    {
        $wanted = CreateWork::uniqueIsrcs($isrcs);

        $work->isrcs()->whereNotIn('isrc', $wanted)->delete();

        $existing = $work->isrcs()->pluck('isrc')->all();

        foreach (array_diff($wanted, $existing) as $isrc) {
            $work->isrcs()->create(['isrc' => $isrc]);
        }
    }
}
