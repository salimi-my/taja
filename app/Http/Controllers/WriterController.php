<?php

namespace App\Http\Controllers;

use App\Actions\Lists\WriterList;
use App\Actions\Reports\StreamCsv;
use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RightsType;
use App\Http\Requests\Writers\StoreWriterRequest;
use App\Http\Requests\Writers\UpdateWriterRequest;
use App\Http\Requests\Writers\WriterIndexRequest;
use App\Http\Resources\WorkPartyResource;
use App\Http\Resources\WriterDetailResource;
use App\Http\Resources\WriterResource;
use App\Models\Country;
use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WriterController extends Controller
{
    /**
     * Display a paginated, searchable and filterable list of writers.
     */
    public function index(WriterIndexRequest $request, WriterList $list): Response
    {
        $filters = $request->filters();

        $writers = $list->query($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('writers/index', [
            'writers' => WriterResource::collection($writers),
            'filters' => $filters,
            ...$this->formOptions(),
            'perPageOptions' => WriterIndexRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download every writer matching the current filters as a CSV file.
     */
    public function export(WriterIndexRequest $request, WriterList $list, StreamCsv $streamCsv): StreamedResponse
    {
        return $streamCsv(
            'writers-'.now()->toDateString().'.csv',
            $list->csvHeaders(),
            $list->csvRows($list->query($request->filters())->lazy()),
        );
    }

    /**
     * Display the given writer with every field.
     */
    public function show(Request $request, Writer $writer): Response
    {
        Gate::authorize('view', $writer);

        $writer->load(['publisher', 'aliases']);

        $includeEnded = $request->boolean('include_ended');

        return Inertia::render('writers/show', [
            'writer' => new WriterDetailResource($writer),
            'catalogue' => WorkPartyResource::collection(
                $writer->parties()->forCatalogue($includeEnded)->get(),
            ),
            'includeEnded' => $includeEnded,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Display the form to create a writer.
     */
    public function create(): Response
    {
        Gate::authorize('create', Writer::class);

        return Inertia::render('writers/create', $this->formOptions());
    }

    /**
     * Display the form to edit the given writer.
     */
    public function edit(Writer $writer): Response
    {
        Gate::authorize('update', $writer);

        $writer->load(['publisher', 'aliases']);

        return Inertia::render('writers/edit', [
            'writer' => new WriterDetailResource($writer),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created writer with its pseudonyms.
     */
    public function store(StoreWriterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $writer = DB::transaction(function () use ($validated): Writer {
            $writer = Writer::create(Arr::except($validated, 'aliases'));

            $this->syncAliases($writer, $validated['aliases'] ?? []);

            return $writer;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Writer created.')]);

        return redirect()->route('writers.show', $writer);
    }

    /**
     * Update the given writer and replace its pseudonyms.
     */
    public function update(UpdateWriterRequest $request, Writer $writer): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $writer): void {
            $writer->update(Arr::except($validated, 'aliases'));

            $this->syncAliases($writer, $validated['aliases'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Writer updated.')]);

        return redirect()->route('writers.show', $writer);
    }

    /**
     * Delete the given writer. It is soft-deleted so its history stays intact.
     */
    public function destroy(Writer $writer): RedirectResponse
    {
        Gate::authorize('delete', $writer);

        if ($writer->hasCurrentParties()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This writer is still a party of one or more works. Remove the writer from those works first.'),
            ]);

            return back();
        }

        $writer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Writer deleted.')]);

        return redirect()->route('writers.index');
    }

    /**
     * Replace the pseudonyms of a writer. Positions follow the submitted order.
     *
     * @param  list<array{name: string, ipi_base_number?: string|null}>  $aliases
     */
    private function syncAliases(Writer $writer, array $aliases): void
    {
        $writer->aliases()->delete();

        foreach ($aliases as $index => $alias) {
            $writer->aliases()->create([
                'name' => $alias['name'],
                'ipi_base_number' => $alias['ipi_base_number'] ?? null,
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * The option lists used by the filters and by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'societies' => Writer::query()->distinct()->orderBy('cmo_society')->pluck('cmo_society')->all(),
            'publishers' => Publisher::query()
                ->orderBy('name')
                ->get(['id', 'name', 'publisher_code'])
                ->map(fn (Publisher $publisher): array => [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'publisher_code' => $publisher->publisher_code,
                ])
                ->all(),
            'roles' => array_values(array_filter(
                PartyRole::options(),
                fn (array $option): bool => PartyRole::from($option['value'])->isWriterRole(),
            )),
            'countries' => Country::query()->orderBy('name')->get(['code', 'name'])->all(),
            'rightsTypes' => RightsType::options(),
            'statuses' => MasterStatus::options(),
        ];
    }
}
