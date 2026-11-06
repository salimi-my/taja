<?php

namespace App\Actions\Reports;

use App\Actions\Works\SummarizeRightsHistory;
use App\Models\RightsHistory;
use App\Models\Work;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The rights history report: every entry of the change log of every work.
 */
class RightsHistoryReport
{
    public function __construct(private readonly SummarizeRightsHistory $summarize) {}

    /**
     * @param  array{
     *     search: string,
     *     work_id: int|null,
     *     change_types: list<string>,
     *     users: list<string>,
     *     effective_from: string,
     *     effective_to: string,
     *     recorded_from: string,
     *     recorded_to: string,
     *     sort: string,
     *     direction: 'asc'|'desc',
     * }  $filters
     * @return Builder<RightsHistory>
     */
    public function query(array $filters): Builder
    {
        return RightsHistory::query()
            ->with(['work', 'user', 'workParty'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->whereHas(
                'work',
                fn (Builder $work) => $work->withTrashed()->search($filters['search']),
            ))
            ->when($filters['work_id'] !== null, fn (Builder $query) => $query->where('work_id', $filters['work_id']))
            ->when($filters['change_types'] !== [], fn (Builder $query) => $query->whereIn('change_type', $filters['change_types']))
            ->when($filters['users'] !== [], fn (Builder $query) => $query->whereIn('user_id', $filters['users']))
            ->when($filters['effective_from'] !== '', fn (Builder $query) => $query->whereDate('effective_date', '>=', $filters['effective_from']))
            ->when($filters['effective_to'] !== '', fn (Builder $query) => $query->whereDate('effective_date', '<=', $filters['effective_to']))
            ->when($filters['recorded_from'] !== '', fn (Builder $query) => $query->whereDate('recorded_at', '>=', $filters['recorded_from']))
            ->when($filters['recorded_to'] !== '', fn (Builder $query) => $query->whereDate('recorded_at', '<=', $filters['recorded_to']))
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderBy('id', $filters['direction']);
    }

    /**
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'Effective_Date', 'Recorded_At', 'TAP_Number', 'Work_ID', 'Work_Title', 'Change_Type',
            'Summary', 'Recorded_By', 'Import_ID', 'Remarks',
        ];
    }

    /**
     * @param  iterable<RightsHistory>  $entries
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $entries): Generator
    {
        foreach ($entries as $entry) {
            /** @var Work $work */
            $work = $entry->work;

            yield [
                $entry->effective_date->toDateString(),
                $entry->recorded_at->toDateTimeString(),
                $work->tap_number,
                $work->macp_work_id,
                $work->displayTitle(),
                $entry->change_type->label(),
                ($this->summarize)($entry),
                $entry->user?->name,
                $entry->csv_import_id,
                $entry->remarks,
            ];
        }
    }
}
