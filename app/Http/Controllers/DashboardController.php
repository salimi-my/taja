<?php

namespace App\Http\Controllers;

use App\Actions\Reports\IncompleteSplitReport;
use App\Enums\RegistrationStatus;
use App\Http\Resources\RightsHistoryResource;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\Work;
use App\Models\Writer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of history entries and imports listed on the dashboard.
     */
    private const RECENT_HISTORY_LIMIT = 8;

    private const RECENT_IMPORTS_LIMIT = 5;

    /**
     * Display the dashboard with the catalogue counts, the works that need
     * attention, the latest rights changes and, for admins, the latest imports.
     */
    public function __invoke(Request $request, IncompleteSplitReport $incompleteSplit): Response
    {
        $worksByStatus = Work::query()->toBase()
            ->selectRaw('registration_status, COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        return Inertia::render('dashboard', [
            'stats' => [
                'works' => Work::count(),
                'writers' => Writer::count(),
                'publishers' => Publisher::count(),
                'incomplete_splits' => $incompleteSplit->count(),
            ],
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
            'recentImports' => $request->user()->isAdmin()
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
                        'created_at' => $csvImport->created_at?->toIso8601String(),
                    ])
                    ->all()
                : null,
        ]);
    }
}
