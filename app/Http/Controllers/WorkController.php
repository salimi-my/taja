<?php

namespace App\Http\Controllers;

use App\Actions\Works\CreateWork;
use App\Actions\Works\UpdateWork;
use App\Enums\ConflictStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Http\Requests\Works\ShowWorkRequest;
use App\Http\Requests\Works\StoreWorkRequest;
use App\Http\Requests\Works\UpdateWorkRequest;
use App\Http\Requests\Works\WorkIndexRequest;
use App\Http\Resources\RightsHistoryResource;
use App\Http\Resources\WorkDetailResource;
use App\Http\Resources\WorkPartyResource;
use App\Http\Resources\WorkResource;
use App\Models\Country;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\Writer;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    /**
     * Display a paginated, searchable and filterable list of works.
     */
    public function index(WorkIndexRequest $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'registration_statuses' => $request->array('registration_statuses'),
            'conflict_statuses' => $request->array('conflict_statuses'),
            'languages' => $request->array('languages'),
            'genres' => $request->array('genres'),
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'sort' => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
            'per_page' => $request->integer('per_page', 10),
        ];

        $works = Work::query()
            ->with('currentParties')
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['registration_statuses'] !== [], fn (Builder $query) => $query->whereIn('registration_status', $filters['registration_statuses']))
            ->when($filters['conflict_statuses'] !== [], fn (Builder $query) => $query->whereIn('conflict_status', $filters['conflict_statuses']))
            ->when($filters['languages'] !== [], fn (Builder $query) => $query->whereIn('language', $filters['languages']))
            ->when($filters['genres'] !== [], fn (Builder $query) => $query->whereIn('genre', $filters['genres']))
            ->when($filters['start_date'] !== '', fn (Builder $query) => $query->whereDate('registration_date', '>=', $filters['start_date']))
            ->when($filters['end_date'] !== '', fn (Builder $query) => $query->whereDate('registration_date', '<=', $filters['end_date']))
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('works/index', [
            'works' => WorkResource::collection($works),
            'filters' => $filters,
            ...$this->filterOptions(),
            'perPageOptions' => WorkIndexRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Display the given work with every field, its parties and its history.
     *
     * The parties are the rows in effect today, or on the `as_of` date.
     */
    public function show(ShowWorkRequest $request, Work $work): Response
    {
        $asOf = $request->filled('as_of') ? $request->string('as_of')->toString() : null;

        $work->load('isrcs');

        $parties = $asOf === null
            ? $work->currentParties()
            : $work->parties()->effectiveOn($asOf);

        return Inertia::render('works/show', [
            'work' => new WorkDetailResource($work),
            'parties' => WorkPartyResource::collection($parties->with(['writer', 'publisher'])->get()),
            'asOf' => $asOf,
            'partyOptions' => $request->user()->can('update', $work) ? $this->partyOptions() : null,
            'history' => Inertia::defer(fn () => RightsHistoryResource::collection(
                $work->rightsHistories()
                    ->with(['user', 'workParty'])
                    ->paginate(15, pageName: 'history_page')
                    ->withQueryString(),
            )),
        ]);
    }

    /**
     * Display the form to create a work.
     */
    public function create(): Response
    {
        Gate::authorize('create', Work::class);

        return Inertia::render('works/create', $this->formOptions());
    }

    /**
     * Display the form to edit the given work.
     */
    public function edit(Work $work): Response
    {
        Gate::authorize('update', $work);

        $work->load('isrcs');

        return Inertia::render('works/edit', [
            'work' => new WorkDetailResource($work),
            'parties' => WorkPartyResource::collection(
                $work->currentParties()->with(['writer', 'publisher'])->get(),
            ),
            ...$this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created work with its ISRCs and parties.
     */
    public function store(StoreWorkRequest $request, CreateWork $createWork): RedirectResponse
    {
        $work = $createWork($request->validated(), $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Work created.')]);

        return redirect()->route('works.show', $work);
    }

    /**
     * Update the work fields and ISRCs. Parties are left untouched.
     */
    public function update(UpdateWorkRequest $request, Work $work, UpdateWork $updateWork): RedirectResponse
    {
        $updateWork($work, $request->validated(), $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Work updated.')]);

        return redirect()->route('works.show', $work);
    }

    /**
     * Delete the given work. It is soft-deleted so its history stays intact.
     */
    public function destroy(Work $work): RedirectResponse
    {
        Gate::authorize('delete', $work);

        $work->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Work deleted.')]);

        return redirect()->route('works.index');
    }

    /**
     * The registration and conflict status options.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function statusOptions(): array
    {
        return [
            'registrationStatuses' => RegistrationStatus::options(),
            'conflictStatuses' => ConflictStatus::options(),
        ];
    }

    /**
     * The option lists used by the list filters.
     *
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        $distinct = fn (string $column): array => Work::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            ...$this->statusOptions(),
            'languages' => $distinct('language'),
            'genres' => $distinct('genre'),
        ];
    }

    /**
     * The option lists used by the create form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [...$this->statusOptions(), ...$this->partyOptions()];
    }

    /**
     * The option lists the party forms need: roles, countries and the writers
     * and publishers that can be picked.
     *
     * @return array<string, mixed>
     */
    private function partyOptions(): array
    {
        return [
            'roles' => PartyRole::options(),
            'countries' => Country::query()->orderBy('name')->get(['code', 'name'])->all(),
            'writers' => Writer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'writer_code', 'ipi_name_number', 'default_role'])
                ->map(fn (Writer $writer): array => [
                    'id' => $writer->id,
                    'name' => $writer->name,
                    'code' => $writer->writer_code,
                    'ipi_name_number' => $writer->ipi_name_number,
                    'default_role' => $writer->default_role?->value,
                ])
                ->all(),
            'publishers' => Publisher::query()
                ->orderBy('name')
                ->get(['id', 'name', 'publisher_code', 'ipi_name_number'])
                ->map(fn (Publisher $publisher): array => [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'code' => $publisher->publisher_code,
                    'ipi_name_number' => $publisher->ipi_name_number,
                ])
                ->all(),
        ];
    }
}
