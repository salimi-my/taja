<?php

namespace App\Actions\Reports;

use App\Models\Work;
use App\Models\WorkParty;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The incomplete split report: works that have no parties in effect, or whose
 * shares do not add up to 100 for a rights type. Performance and mechanical
 * are always checked, sync and lyrics only when a party has a value.
 *
 * The totals are over the party rows in effect today. Rows of parties that
 * TAJA does not control still count, because every owner of the work is listed.
 * A party without an effective start date cannot exist, as the column is required.
 */
class IncompleteSplitReport
{
    /**
     * The rights types with their share column.
     *
     * @var array<string, string>
     */
    private const TYPES = [
        'performance' => 'performance_share',
        'mechanical' => 'mechanical_share',
        'sync' => 'sync_share',
        'lyrics' => 'lyrics_share',
    ];

    /**
     * The issues a work can have, with the label shown in the filter.
     *
     * @var array<string, string>
     */
    public const ISSUE_LABELS = [
        'no_parties' => 'No parties in effect',
        'performance' => 'Performance total',
        'mechanical' => 'Mechanical total',
        'sync' => 'Sync total',
        'lyrics' => 'Lyrics total',
    ];

    /**
     * The SQL that tells a work has an issue, over the grouped totals.
     * A work without parties only has the first issue.
     *
     * @var array<string, string>
     */
    private const ISSUE_CONDITIONS = [
        'no_parties' => 'totals.work_id IS NULL',
        'performance' => 'totals.work_id IS NOT NULL AND ABS(COALESCE(totals.performance_total, 0) - 100) > 0.004',
        'mechanical' => 'totals.work_id IS NOT NULL AND ABS(COALESCE(totals.mechanical_total, 0) - 100) > 0.004',
        'sync' => 'totals.sync_values_count > 0 AND ABS(COALESCE(totals.sync_total, 0) - 100) > 0.004',
        'lyrics' => 'totals.lyrics_values_count > 0 AND ABS(COALESCE(totals.lyrics_total, 0) - 100) > 0.004',
    ];

    /**
     * The incomplete works, with the party count and totals as attributes.
     *
     * @param  array{search: string, registration_statuses: list<string>, conflict_statuses: list<string>, issues: list<string>, sort: string, direction: 'asc'|'desc'}  $filters
     * @return Builder<Work>
     */
    public function query(array $filters): Builder
    {
        $query = $this->incompleteWorks()
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['registration_statuses'] !== [], fn (Builder $query) => $query->whereIn('works.registration_status', $filters['registration_statuses']))
            ->when($filters['conflict_statuses'] !== [], fn (Builder $query) => $query->whereIn('works.conflict_status', $filters['conflict_statuses']))
            ->when($filters['issues'] !== [], fn (Builder $query) => $query->where(
                fn (Builder $query) => $this->whereHasAnyIssue($query, $filters['issues']),
            ));

        return $query->orderBy($filters['sort'], $filters['direction'])->orderBy('id');
    }

    /**
     * The number of incomplete works, e.g. for the dashboard.
     */
    public function count(): int
    {
        return $this->incompleteWorks()->count();
    }

    /**
     * The reasons a work is incomplete, in words.
     *
     * @return list<string>
     */
    public function reasons(Work $work): array
    {
        if ((int) $work->getAttribute('current_parties_count') === 0) {
            return ['No parties in effect'];
        }

        $reasons = [];

        foreach (array_keys(self::TYPES) as $type) {
            $total = $this->total($work, $type);

            if ($total !== null && abs($total - 100) > 0.004) {
                $reasons[] = ucfirst($type).' shares add up to '.rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.').'%';
            }
        }

        return $reasons;
    }

    /**
     * The total of a rights type, or `null` for sync and lyrics when no party has a value.
     */
    public function total(Work $work, string $type): ?float
    {
        $optional = in_array($type, ['sync', 'lyrics'], true);

        if ($optional && (int) $work->getAttribute("{$type}_values_count") === 0) {
            return null;
        }

        return round((float) $work->getAttribute("{$type}_total"), 2);
    }

    /**
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'TAP_Number', 'Work_ID', 'Work_Title', 'Registration_Status', 'Parties_In_Effect',
            'Performance_Total', 'Mechanical_Total', 'Sync_Total', 'Lyrics_Total', 'Issues',
        ];
    }

    /**
     * @param  iterable<Work>  $works
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $works): Generator
    {
        foreach ($works as $work) {
            yield [
                $work->tap_number,
                $work->macp_work_id,
                $work->displayTitle(),
                $work->registration_status->label(),
                (int) $work->getAttribute('current_parties_count'),
                $this->total($work, 'performance'),
                $this->total($work, 'mechanical'),
                $this->total($work, 'sync'),
                $this->total($work, 'lyrics'),
                implode('; ', $this->reasons($work)),
            ];
        }
    }

    /**
     * Every work with its party count and totals, narrowed to the incomplete ones.
     * The totals come from one grouped join, which works on every database.
     *
     * @return Builder<Work>
     */
    private function incompleteWorks(): Builder
    {
        $totals = WorkParty::query()
            ->current()
            ->select('work_id')
            ->selectRaw('COUNT(*) as parties_count')
            ->selectRaw('SUM(performance_share) as performance_total')
            ->selectRaw('SUM(mechanical_share) as mechanical_total')
            ->selectRaw('SUM(sync_share) as sync_total')
            ->selectRaw('COUNT(sync_share) as sync_values_count')
            ->selectRaw('SUM(lyrics_share) as lyrics_total')
            ->selectRaw('COUNT(lyrics_share) as lyrics_values_count')
            ->groupBy('work_id');

        return Work::query()
            ->leftJoinSub($totals, 'totals', 'totals.work_id', '=', 'works.id')
            ->select('works.*')
            ->selectRaw('COALESCE(totals.parties_count, 0) as current_parties_count')
            ->selectRaw('COALESCE(totals.performance_total, 0) as performance_total')
            ->selectRaw('COALESCE(totals.mechanical_total, 0) as mechanical_total')
            ->selectRaw('COALESCE(totals.sync_total, 0) as sync_total')
            ->selectRaw('COALESCE(totals.sync_values_count, 0) as sync_values_count')
            ->selectRaw('COALESCE(totals.lyrics_total, 0) as lyrics_total')
            ->selectRaw('COALESCE(totals.lyrics_values_count, 0) as lyrics_values_count')
            ->where(fn (Builder $query) => $this->whereHasAnyIssue($query, array_keys(self::ISSUE_CONDITIONS)));
    }

    /**
     * Narrow the works to those with at least one of the given issues.
     *
     * @param  Builder<Work>  $query
     * @param  list<string>  $issues
     */
    private function whereHasAnyIssue(Builder $query, array $issues): void
    {
        foreach ($issues as $issue) {
            $query->orWhereRaw('('.self::ISSUE_CONDITIONS[$issue].')');
        }
    }
}
