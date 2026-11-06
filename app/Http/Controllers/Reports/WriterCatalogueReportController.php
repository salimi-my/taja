<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\StreamCsv;
use App\Actions\Reports\WriterCatalogueReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\WriterCatalogueReportRequest;
use App\Http\Resources\WorkPartyResource;
use App\Models\Writer;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WriterCatalogueReportController extends Controller
{
    /**
     * Display the catalogue of the chosen writer.
     */
    public function index(WriterCatalogueReportRequest $request, WriterCatalogueReport $report): Response
    {
        $filters = $request->filters();

        return Inertia::render('reports/writer-catalogue', [
            'rows' => WorkPartyResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => [
                'search' => '',
                'sort' => 'effective_start_date',
                'direction' => 'desc',
                ...$filters,
            ],
            'writer' => $this->writerSummary($filters['writer_id']),
            'writers' => Writer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'writer_code'])
                ->map(fn (Writer $writer): array => [
                    'id' => $writer->id,
                    'name' => $writer->name,
                    'code' => $writer->writer_code,
                ])
                ->all(),
            'perPageOptions' => WriterCatalogueReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the catalogue of the chosen writer as a CSV file.
     */
    public function export(WriterCatalogueReportRequest $request, WriterCatalogueReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        $filters = $request->filters();

        return $streamCsv(
            'writer-catalogue-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($filters)->lazy()),
        );
    }

    /**
     * @return array{id: int, name: string, code: string}|null
     */
    private function writerSummary(?int $writerId): ?array
    {
        $writer = $writerId === null ? null : Writer::query()->find($writerId);

        return $writer === null ? null : [
            'id' => $writer->id,
            'name' => $writer->name,
            'code' => $writer->writer_code,
        ];
    }
}
