<?php

namespace App\Actions\Reports;

use App\Models\Work;
use App\Models\WorkParty;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The song ownership split report: the parties and shares of each work, today
 * or as of a date.
 */
class SongOwnershipReport
{
    /**
     * The works with the party rows in effect loaded on each.
     *
     * @param  array{search: string, work_id: int|null, as_of: string|null}  $filters
     * @return Builder<Work>
     */
    public function query(array $filters): Builder
    {
        return Work::query()
            ->with(['parties' => function (HasMany $parties) use ($filters): void {
                $filters['as_of'] === null ? $parties->current() : $parties->effectiveOn($filters['as_of']);

                $parties->with(['writer', 'publisher']);
            }])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['work_id'] !== null, fn (Builder $query) => $query->whereKey($filters['work_id']))
            ->orderBy('tap_number')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'TAP_Number', 'Work_ID', 'Work_Title', 'As_Of', 'Writer_Roles', 'Contributor_Name', 'Party_ID',
            'Perform_Share_Owner_Rights', 'Controlled_Mechanical_Share', 'Controlled_Sync_Share', 'Controlled_Lyrics_Share',
            'Controlled', 'Territories_Controlled', 'Territory_Exclusions', 'Effective_Start_Date', 'Effective_End_Date',
        ];
    }

    /**
     * One row per party in effect. Works without parties have no rows.
     *
     * @param  iterable<Work>  $works
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $works, ?string $asOf): Generator
    {
        foreach ($works as $work) {
            /** @var WorkParty $party */
            foreach ($work->parties as $party) {
                yield [
                    $work->tap_number,
                    $work->macp_work_id,
                    $work->displayTitle(),
                    $asOf ?? 'Current',
                    $party->role->value,
                    $party->contributor_name,
                    $party->writer->writer_code ?? $party->publisher->publisher_code,
                    $party->performance_share,
                    $party->mechanical_share,
                    $party->sync_share,
                    $party->lyrics_share,
                    $party->is_controlled,
                    implode('|', $party->territories_controlled),
                    implode('|', $party->territory_exclusions ?? []),
                    $party->effective_start_date->toDateString(),
                    $party->effective_end_date?->toDateString(),
                ];
            }
        }
    }
}
