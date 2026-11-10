<?php

namespace App\Actions\Reports;

use App\Actions\Masters\SummarizeMasterHistory;
use App\Models\MasterHistory;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The master history report: every entry of the change log of every writer
 * and publisher.
 */
class MasterHistoryReport
{
    public function __construct(private readonly SummarizeMasterHistory $summarize) {}

    /**
     * @param  array{
     *     search: string,
     *     types: list<string>,
     *     change_types: list<string>,
     *     users: list<string>,
     *     recorded_from: string,
     *     recorded_to: string,
     *     sort: string,
     *     direction: 'asc'|'desc',
     * }  $filters
     * @return Builder<MasterHistory>
     */
    public function query(array $filters): Builder
    {
        return MasterHistory::query()
            ->with(['subject', 'user'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('subject_name', 'like', $term)
                    ->orWhere('subject_code', 'like', $term));
            })
            ->when($filters['types'] !== [], fn (Builder $query) => $query->whereIn('subject_type', $filters['types']))
            ->when($filters['change_types'] !== [], fn (Builder $query) => $query->whereIn('change_type', $filters['change_types']))
            ->when($filters['users'] !== [], fn (Builder $query) => $query->whereIn('user_id', $filters['users']))
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
            'Recorded_At', 'Record_Type', 'Code', 'Name', 'Change_Type',
            'Summary', 'Recorded_By', 'Import_ID', 'Remarks',
        ];
    }

    /**
     * @param  iterable<MasterHistory>  $entries
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $entries): Generator
    {
        foreach ($entries as $entry) {
            yield [
                $entry->recorded_at->toDateTimeString(),
                Str::ucfirst($entry->subject_type),
                $entry->subject_code,
                $entry->subject_name,
                $entry->change_type->label(),
                ($this->summarize)($entry),
                $entry->user?->name,
                $entry->csv_import_id,
                $entry->remarks,
            ];
        }
    }
}
