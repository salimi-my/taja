<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\IncompleteSplitReport;
use App\Actions\Reports\StreamCsv;
use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\IncompleteSplitReportRequest;
use App\Http\Resources\IncompleteSplitResource;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncompleteSplitReportController extends Controller
{
    /**
     * Display the works whose shares do not add up to 100.
     */
    public function index(IncompleteSplitReportRequest $request, IncompleteSplitReport $report): Response
    {
        $filters = $request->filters();

        return Inertia::render('reports/incomplete-split', [
            'rows' => IncompleteSplitResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => $filters,
            'registrationStatuses' => RegistrationStatus::options(),
            'conflictStatuses' => ConflictStatus::options(),
            'issueOptions' => array_map(
                fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
                array_keys(IncompleteSplitReport::ISSUE_LABELS),
                IncompleteSplitReport::ISSUE_LABELS,
            ),
            'perPageOptions' => IncompleteSplitReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the incomplete works as a CSV file.
     */
    public function export(IncompleteSplitReportRequest $request, IncompleteSplitReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        return $streamCsv(
            'incomplete-split-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($request->filters())->lazy()),
        );
    }
}
