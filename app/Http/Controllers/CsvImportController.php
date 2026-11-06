<?php

namespace App\Http\Controllers;

use App\Actions\Imports\BuildCsvTemplate;
use App\Actions\Imports\CancelCsvImport;
use App\Actions\Imports\ConfirmCsvImport;
use App\Actions\Imports\ImportColumn;
use App\Actions\Imports\ImportDefinition;
use App\Actions\Imports\ImportRegistry;
use App\Actions\Imports\InvalidImportFile;
use App\Actions\Imports\PreviewCsvImport;
use App\Enums\CsvImportType;
use App\Http\Requests\Imports\StoreCsvImportRequest;
use App\Http\Resources\CsvImportResource;
use App\Models\CsvImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportController extends Controller
{
    /**
     * Display the upload page with the field guide of the import type.
     */
    public function create(CsvImportType $type): Response
    {
        Gate::authorize('viewAny', CsvImport::class);

        $definition = $this->definitionFor($type);

        return Inertia::render('imports/create', [
            'type' => $type->value,
            'typeLabel' => $type->label(),
            'columns' => array_map(fn (ImportColumn $column): array => $column->toGuide(), $definition->columns()),
            'maxRows' => (int) config('imports.max_rows'),
            'maxFileMegabytes' => config('imports.max_file_kilobytes') / 1024,
        ]);
    }

    /**
     * Upload a file, validate it and show the preview.
     */
    public function store(StoreCsvImportRequest $request, CsvImportType $type, PreviewCsvImport $preview): RedirectResponse
    {
        $csvImport = $preview($request->file('file'), $this->definitionFor($type), $request->user());

        return redirect()->route('imports.show', $csvImport);
    }

    /**
     * Display the preview, or the result once the import was confirmed.
     */
    public function show(CsvImport $csvImport): Response
    {
        Gate::authorize('view', $csvImport);

        $csvImport->load('user');

        return Inertia::render('imports/show', [
            'csvImport' => new CsvImportResource($csvImport),
        ]);
    }

    /**
     * Start importing the valid rows of the previewed file in the background.
     */
    public function confirm(CsvImport $csvImport, ConfirmCsvImport $confirm): RedirectResponse
    {
        Gate::authorize('update', $csvImport);

        try {
            $csvImport = $confirm($csvImport);
        } catch (InvalidImportFile $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->route('imports.show', $csvImport);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The import started. You can leave this page, it keeps running.')]);

        return redirect()->route('imports.show', $csvImport);
    }

    /**
     * Cancel the previewed file without importing anything.
     */
    public function cancel(CsvImport $csvImport, CancelCsvImport $cancel): RedirectResponse
    {
        Gate::authorize('update', $csvImport);

        try {
            $cancel($csvImport);
        } catch (InvalidImportFile $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->route('imports.show', $csvImport);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Import cancelled.')]);

        return redirect()->route('imports.create', $csvImport->type);
    }

    /**
     * Download the template: the header row only.
     */
    public function template(CsvImportType $type, BuildCsvTemplate $build): StreamedResponse
    {
        Gate::authorize('viewAny', CsvImport::class);

        return $this->csvDownload($build($this->definitionFor($type)), "{$type->value}-template.csv");
    }

    /**
     * Download the template with fake example rows.
     */
    public function example(CsvImportType $type, BuildCsvTemplate $build): StreamedResponse
    {
        Gate::authorize('viewAny', CsvImport::class);

        return $this->csvDownload($build($this->definitionFor($type), withExample: true), "{$type->value}-example.csv");
    }

    /**
     * Download the skipped rows with an `Error` column.
     */
    public function errors(CsvImport $csvImport): StreamedResponse
    {
        Gate::authorize('view', $csvImport);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($csvImport->errorsFilePath()), 404);

        return $disk->download($csvImport->errorsFilePath(), "{$csvImport->type->value}-errors.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The definition of an import type, or a 404 when the type is not available yet.
     */
    private function definitionFor(CsvImportType $type): ImportDefinition
    {
        return ImportRegistry::find($type) ?? abort(404);
    }

    private function csvDownload(string $content, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
