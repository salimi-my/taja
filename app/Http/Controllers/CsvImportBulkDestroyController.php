<?php

namespace App\Http\Controllers;

use App\Actions\Imports\DeleteCsvImport;
use App\Http\Requests\Imports\BulkDestroyCsvImportRequest;
use App\Models\CsvImport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CsvImportBulkDestroyController extends Controller
{
    /**
     * Delete the selected imports, skipping any that are still running.
     */
    public function __invoke(BulkDestroyCsvImportRequest $request, DeleteCsvImport $delete): RedirectResponse
    {
        $deleted = CsvImport::query()
            ->whereIn('id', $request->validated('ids'))
            ->get()
            ->filter(fn (CsvImport $csvImport): bool => $request->user()->can('delete', $csvImport))
            ->each(fn (CsvImport $csvImport) => $delete($csvImport))
            ->count();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count import deleted.|:count imports deleted.', $deleted),
        ]);

        return back();
    }
}
