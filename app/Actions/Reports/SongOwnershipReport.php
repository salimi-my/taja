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
     * @param  array{search: string, work_id: int|null, as_of: string|null, registration_statuses: list<string>, conflict_statuses: list<string>, controlled: list<string>}  $filters
     * @return Builder<Work>
     */
    public function query(array $filters): Builder
    {
        return Work::query()
            ->with(['parties' => function (HasMany $parties) use ($filters): void {
                $this->limitToInEffect($parties, $filters['as_of']);
                $parties->with(['writer', 'publisher']);
            }])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['work_id'] !== null, fn (Builder $query) => $query->whereKey($filters['work_id']))
            ->when($filters['registration_statuses'] !== [], fn (Builder $query) => $query->whereIn('registration_status', $filters['registration_statuses']))
            ->when($filters['conflict_statuses'] !== [], fn (Builder $query) => $query->whereIn('conflict_status', $filters['conflict_statuses']))
            ->when(count($filters['controlled']) === 1, function (Builder $query) use ($filters): void {
                $hasControlledParty = function (Builder $parties) use ($filters): void {
                    $this->limitToInEffect($parties, $filters['as_of']);
                    $parties->where('is_controlled', true);
                };

                $filters['controlled'][0] === 'yes'
                    ? $query->whereHas('parties', $hasControlledParty)
                    : $query->whereDoesntHave('parties', $hasControlledParty);
            })
            ->orderBy('tap_number')
            ->orderBy('id');
    }

    /**
     * Limit party rows to those in effect today, or on a date.
     *
     * The scopes add their constraints to the given query in place.
     *
     * @param  Builder<WorkParty>|HasMany<WorkParty, Work>  $parties
     */
    private function limitToInEffect(Builder|HasMany $parties, ?string $asOf): void
    {
        if ($asOf === null) {
            $parties->current();

            return;
        }

        $parties->effectiveOn($asOf);
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
