<?php

namespace App\Actions\Reports;

use App\Actions\Users\SummarizeUserHistory;
use App\Models\UserHistory;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The user history report: every entry of the change log of every user account.
 */
class UserHistoryReport
{
    public function __construct(private readonly SummarizeUserHistory $summarize) {}

    /**
     * @param  array{
     *     search: string,
     *     change_types: list<string>,
     *     users: list<string>,
     *     recorded_from: string,
     *     recorded_to: string,
     *     sort: string,
     *     direction: 'asc'|'desc',
     * }  $filters
     * @return Builder<UserHistory>
     */
    public function query(array $filters): Builder
    {
        return UserHistory::query()
            ->with('actor')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('subject_name', 'like', $term)
                    ->orWhere('subject_email', 'like', $term));
            })
            ->when($filters['change_types'] !== [], fn (Builder $query) => $query->whereIn('change_type', $filters['change_types']))
            ->when($filters['users'] !== [], fn (Builder $query) => $query->whereIn('actor_id', $filters['users']))
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
            'Recorded_At', 'Name', 'Email', 'Change_Type', 'Summary', 'Changed_By', 'Remarks',
        ];
    }

    /**
     * @param  iterable<UserHistory>  $entries
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $entries): Generator
    {
        foreach ($entries as $entry) {
            yield [
                $entry->recorded_at->toDateTimeString(),
                $entry->subject_name,
                $entry->subject_email,
                $entry->change_type->label(),
                ($this->summarize)($entry),
                $entry->actor->name ?? $entry->actor_name,
                $entry->remarks,
            ];
        }
    }
}
