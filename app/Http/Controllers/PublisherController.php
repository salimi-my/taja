<?php

namespace App\Http\Controllers;

use App\Enums\MasterStatus;
use App\Enums\RightsType;
use App\Http\Requests\Publishers\PublisherIndexRequest;
use App\Http\Requests\Publishers\StorePublisherRequest;
use App\Http\Requests\Publishers\UpdatePublisherRequest;
use App\Http\Resources\PublisherDetailResource;
use App\Http\Resources\PublisherResource;
use App\Http\Resources\WorkPartyResource;
use App\Models\Country;
use App\Models\Publisher;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PublisherController extends Controller
{
    /**
     * Display a paginated, searchable and filterable list of publishers.
     */
    public function index(PublisherIndexRequest $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->array('status'),
            'societies' => $request->array('societies'),
            'rights_types' => $request->array('rights_types'),
            'territories' => array_map('strtoupper', $request->array('territories')),
            'sort' => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
            'per_page' => $request->integer('per_page', 10),
        ];

        $publishers = Publisher::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('publisher_code', 'like', $term)
                        ->orWhere('ipi_name_number', 'like', $term)
                        ->orWhere('ipi_base_number', 'like', $term)
                        ->orWhere('company_registration_number', 'like', $term);
                });
            })
            ->when($filters['status'] !== [], fn (Builder $query) => $query->whereIn('status', $filters['status']))
            ->when($filters['societies'] !== [], fn (Builder $query) => $query->whereIn('society_cmo', $filters['societies']))
            ->when($filters['rights_types'] !== [], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    foreach ($filters['rights_types'] as $type) {
                        $query->orWhereJsonContains('rights_types_granted', $type);
                    }
                });
            })
            ->when($filters['territories'] !== [], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    foreach ([...$filters['territories'], 'WW'] as $territory) {
                        $query->orWhereJsonContains('territories_granted', $territory);
                    }
                });
            })
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('publishers/index', [
            'publishers' => PublisherResource::collection($publishers),
            'filters' => $filters,
            ...$this->formOptions(),
            'perPageOptions' => PublisherIndexRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Display the given publisher with every field.
     */
    public function show(Request $request, Publisher $publisher): Response
    {
        Gate::authorize('view', $publisher);

        $includeEnded = $request->boolean('include_ended');

        return Inertia::render('publishers/show', [
            'publisher' => new PublisherDetailResource($publisher),
            'catalogue' => WorkPartyResource::collection(
                $publisher->parties()->forCatalogue($includeEnded)->get(),
            ),
            'includeEnded' => $includeEnded,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Display the form to create a publisher.
     */
    public function create(): Response
    {
        Gate::authorize('create', Publisher::class);

        return Inertia::render('publishers/create', $this->formOptions());
    }

    /**
     * Display the form to edit the given publisher.
     */
    public function edit(Publisher $publisher): Response
    {
        Gate::authorize('update', $publisher);

        return Inertia::render('publishers/edit', [
            'publisher' => new PublisherDetailResource($publisher),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created publisher.
     */
    public function store(StorePublisherRequest $request): RedirectResponse
    {
        $publisher = Publisher::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publisher created.')]);

        return redirect()->route('publishers.show', $publisher);
    }

    /**
     * Update the given publisher.
     */
    public function update(UpdatePublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        $publisher->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publisher updated.')]);

        return redirect()->route('publishers.show', $publisher);
    }

    /**
     * Delete the given publisher. It is soft-deleted so its history stays intact.
     */
    public function destroy(Publisher $publisher): RedirectResponse
    {
        Gate::authorize('delete', $publisher);

        if ($publisher->writers()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This publisher still has writers linked to it. Unlink or delete them first.'),
            ]);

            return back();
        }

        if ($publisher->hasCurrentParties()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This publisher is still a party of one or more works. Remove the publisher from those works first.'),
            ]);

            return back();
        }

        $publisher->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publisher deleted.')]);

        return redirect()->route('publishers.index');
    }

    /**
     * The option lists used by the filters and by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'societies' => Publisher::query()->distinct()->orderBy('society_cmo')->pluck('society_cmo')->all(),
            'territories' => Publisher::query()
                ->pluck('territories_granted')
                ->flatten()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'countries' => Country::query()->orderBy('name')->get(['code', 'name'])->all(),
            'rightsTypes' => RightsType::options(),
            'statuses' => MasterStatus::options(),
        ];
    }
}
