<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\SongOwnershipReport;
use App\Actions\Reports\StreamCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\SongOwnershipReportRequest;
use App\Http\Resources\WorkOwnershipResource;
use App\Models\Work;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SongOwnershipReportController extends Controller
{
    /**
     * Display the parties and shares of the works, today or as of a date.
     */
    public function index(SongOwnershipReportRequest $request, SongOwnershipReport $report): Response
    {
        $filters = $request->filters();

        $work = $filters['work_id'] === null ? null : Work::query()->find($filters['work_id']);

        return Inertia::render('reports/song-ownership', [
            'rows' => WorkOwnershipResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => [
                'sort' => 'tap_number',
                'direction' => 'asc',
                ...$filters,
            ],
            'work' => $work === null ? null : [
                'id' => $work->id,
                'tap_number' => $work->tap_number,
                'title' => $work->displayTitle(),
            ],
            'perPageOptions' => SongOwnershipReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the parties and shares of the works as a CSV file.
     */
    public function export(SongOwnershipReportRequest $request, SongOwnershipReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        $filters = $request->filters();

        return $streamCsv(
            'song-ownership-split-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($filters)->lazy(), $filters['as_of']),
        );
    }
}
