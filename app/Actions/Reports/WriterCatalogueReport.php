<?php

namespace App\Actions\Reports;

use App\Models\WorkParty;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The writer catalogue report: every work one writer is a party of.
 */
class WriterCatalogueReport
{
    /**
     * The party rows of the chosen writer. Without a writer there are no rows.
     *
     * @param  array{writer_id: int|null, include_ended: bool, search: string, roles: list<string>, registration_statuses: list<string>, conflict_statuses: list<string>}  $filters
     * @return Builder<WorkParty>
     */
    public function query(array $filters): Builder
    {
        return WorkParty::query()
            ->with('writer')
            ->when(
                $filters['writer_id'] === null,
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->where('writer_id', $filters['writer_id']),
            )
            ->forCatalogue($filters['include_ended'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->whereHas(
                'work',
                fn (Builder $work) => $work->search($filters['search']),
            ))
            ->when($filters['roles'] !== [], fn (Builder $query) => $query->whereIn('role', $filters['roles']))
            ->when($filters['registration_statuses'] !== [], fn (Builder $query) => $query->whereHas(
                'work',
                fn (Builder $work) => $work->whereIn('registration_status', $filters['registration_statuses']),
            ))
            ->when($filters['conflict_statuses'] !== [], fn (Builder $query) => $query->whereHas(
                'work',
                fn (Builder $work) => $work->whereIn('conflict_status', $filters['conflict_statuses']),
            ));
    }

    /**
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'Writer_ID', 'Writer_Name', 'TAP_Number', 'Work_ID', 'Work_Title', 'Role',
            'Perform_Share_Owner_Rights', 'Controlled_Mechanical_Share', 'Controlled_Sync_Share', 'Controlled_Lyrics_Share',
            'Territories_Controlled', 'Effective_Start_Date', 'Effective_End_Date', 'Registration_Status', 'Conflict_Status',
        ];
    }

    /**
     * @param  iterable<WorkParty>  $parties
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $parties): Generator
    {
        foreach ($parties as $party) {
            yield [
                $party->writer?->writer_code,
                $party->writer?->name,
                $party->work->tap_number,
                $party->work->macp_work_id,
                $party->work->displayTitle(),
                $party->role->value,
                $party->performance_share,
                $party->mechanical_share,
                $party->sync_share,
                $party->lyrics_share,
                implode('|', $party->territories_controlled),
                $party->effective_start_date->toDateString(),
                $party->effective_end_date?->toDateString(),
                $party->work->registration_status->label(),
                $party->work->conflict_status->label(),
            ];
        }
    }
}
