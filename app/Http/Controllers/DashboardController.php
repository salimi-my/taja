<?php

namespace App\Http\Controllers;

use App\Actions\Reports\IncompleteSplitReport;
use App\Enums\ConflictStatus;
use App\Enums\CsvImportStatus;
use App\Enums\RegistrationStatus;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\RightsHistoryResource;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of history entries and imports listed on the dashboard.
     */
    private const RECENT_HISTORY_LIMIT = 5;

    private const RECENT_IMPORTS_LIMIT = 5;

    /**
     * The number of publishers in the top publishers list.
     */
    private const TOP_PUBLISHERS_LIMIT = 5;

    /**
     * Display the dashboard with the catalogue counts and their weekly trend,
     * the checks that need attention, the latest rights changes and, for
     * admins, the latest imports. The charts are deferred so the cards and
     * lists render first, and the activity chart covers the requested days.
     */
    public function __invoke(DashboardRequest $request, IncompleteSplitReport $incompleteSplit): Response
    {
        $isAdmin = $request->user()->isAdmin();
        $days = $request->activityDays();

        $worksByStatus = Work::query()->toBase()
            ->selectRaw('registration_status, COUNT(*) as total')
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        return Inertia::render('dashboard', [
            'stats' => [
                ...$this->countWithTrend('works', Work::query()),
                ...$this->countWithTrend('writers', Writer::query()),
                ...$this->countWithTrend('publishers', Publisher::query()),
                'incomplete_splits' => $incompleteSplit->count(),
            ],
            'attention' => $this->attention($incompleteSplit, $isAdmin),
            'registrationBreakdown' => collect(RegistrationStatus::cases())
                ->map(fn (RegistrationStatus $status): array => [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'total' => (int) ($worksByStatus[$status->value] ?? 0),
                ])
                ->all(),
            'recentHistory' => RightsHistoryResource::collection(
                RightsHistory::query()
                    ->with(['work', 'user', 'workParty'])
                    ->latest('recorded_at')
                    ->latest('id')
                    ->limit(self::RECENT_HISTORY_LIMIT)
                    ->get(),
            ),
            'recentImports' => $isAdmin
                ? CsvImport::query()
                    ->with('user')
                    ->latest()
                    ->limit(self::RECENT_IMPORTS_LIMIT)
                    ->get()
                    ->map(fn (CsvImport $csvImport): array => [
                        'id' => $csvImport->id,
                        'type' => $csvImport->type->value,
                        'type_label' => $csvImport->type->label(),
                        'original_filename' => $csvImport->original_filename,
                        'status' => $csvImport->status->value,
                        'status_label' => $csvImport->status->label(),
                        'total_rows' => $csvImport->total_rows,
                        'imported_count' => $csvImport->imported_count,
                        'uploaded_by' => $csvImport->user->name ?? null,
                        'uploaded_by_avatar_url' => $csvImport->user->avatar_url ?? null,
                        'created_at' => $csvImport->created_at?->toIso8601String(),
                    ])
                    ->all()
                : null,
            'activityDays' => $days,
            'activity' => Inertia::defer(fn (): array => $this->activity($days)),
            'conflictBreakdown' => Inertia::defer(fn (): array => $this->conflictBreakdown()),
            'topPublishers' => Inertia::defer(fn (): array => $this->topPublishers()),
        ]);
    }

    /**
     * Count the records of a model, and how many were created this week and the week before.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array<string, int>
     */
    private function countWithTrend(string $name, Builder $query): array
    {
        $weekAgo = now()->subDays(7);
        $twoWeeksAgo = now()->subDays(14);

        $row = $query
            ->selectRaw(
                'count(*) as total,
                coalesce(sum(case when created_at >= ? then 1 else 0 end), 0) as this_week,
                coalesce(sum(case when created_at >= ? and created_at < ? then 1 else 0 end), 0) as last_week',
                [$weekAgo, $twoWeeksAgo, $weekAgo],
            )
            ->toBase()
            ->first();

        return [
            $name => (int) $row->total,
            "{$name}_this_week" => (int) $row->this_week,
            "{$name}_last_week" => (int) $row->last_week,
        ];
    }

    /**
     * List every check with how many records fail it, including the checks
     * that are in order. Failed imports are only checked for admins, and
     * point at the latest failed import.
     *
     * @return list<array{key: string, count: int, target_id: int|null}>
     */
    private function attention(IncompleteSplitReport $incompleteSplit, bool $isAdmin): array
    {
        $items = [
            ['key' => 'incomplete_splits', 'count' => $incompleteSplit->count(), 'target_id' => null],
            [
                'key' => 'works_in_conflict',
                'count' => Work::query()->where('conflict_status', '!=', ConflictStatus::NoConflict)->count(),
                'target_id' => null,
            ],
            [
                'key' => 'rejected_registrations',
                'count' => Work::query()->where('registration_status', RegistrationStatus::Rejected)->count(),
                'target_id' => null,
            ],
        ];

        if ($isAdmin) {
            $failed = CsvImport::query()->where('status', CsvImportStatus::Failed);

            $items[] = [
                'key' => 'failed_imports',
                'count' => (clone $failed)->count(),
                'target_id' => $failed->latest()->latest('id')->value('id'),
            ];
        }

        return $items;
    }

    /**
     * Count the works created and the rights changes recorded on each of the
     * last given days, with empty days as zero.
     *
     * @return list<array{date: string, works: int, changes: int}>
     */
    private function activity(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $works = $this->countPerDay(Work::query(), 'created_at', $from);
        $changes = $this->countPerDay(RightsHistory::query(), 'recorded_at', $from);

        $series = [];

        foreach (CarbonPeriod::create($from, now()->startOfDay()) as $day) {
            $date = $day->toDateString();

            $series[] = [
                'date' => $date,
                'works' => $works[$date] ?? 0,
                'changes' => $changes[$date] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Count the records whose column falls on each day since the given moment.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  'created_at'|'recorded_at'  $column
     * @return array<string, int>
     */
    private function countPerDay(Builder $query, string $column, DateTimeInterface $from): array
    {
        $day = match ($column) {
            'created_at' => new Expression('DATE(created_at) as day'),
            'recorded_at' => new Expression('DATE(recorded_at) as day'),
        };

        return $query
            ->select($day)
            ->selectRaw('count(*) as total')
            ->where($column, '>=', $from)
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Count the works of every conflict status, zeros included.
     *
     * @return list<array{value: string, label: string, total: int}>
     */
    private function conflictBreakdown(): array
    {
        $totals = Work::query()->toBase()
            ->selectRaw('conflict_status, COUNT(*) as total')
            ->groupBy('conflict_status')
            ->pluck('total', 'conflict_status');

        return array_map(fn (ConflictStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
            'total' => (int) ($totals[$status->value] ?? 0),
        ], ConflictStatus::cases());
    }

    /**
     * Find the publishers that are a current party of the most works.
     *
     * @return list<array{id: int, name: string, code: string, total: int}>
     */
    private function topPublishers(): array
    {
        $rows = WorkParty::query()
            ->current()
            ->join('works', 'works.id', '=', 'work_parties.work_id')
            ->join('publishers', 'publishers.id', '=', 'work_parties.publisher_id')
            ->whereNull('works.deleted_at')
            ->whereNull('publishers.deleted_at')
            ->selectRaw('publishers.id, publishers.name, publishers.publisher_code, COUNT(DISTINCT work_parties.work_id) as total')
            ->groupBy('publishers.id', 'publishers.name', 'publishers.publisher_code')
            ->orderByDesc('total')
            ->orderBy('publishers.name')
            ->limit(self::TOP_PUBLISHERS_LIMIT)
            ->toBase()
            ->get();

        return array_values($rows->map(fn (object $row): array => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'code' => (string) $row->publisher_code,
            'total' => (int) $row->total,
        ])->all());
    }
}
