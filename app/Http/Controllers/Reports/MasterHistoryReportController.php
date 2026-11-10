<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\MasterHistoryReport;
use App\Actions\Reports\StreamCsv;
use App\Enums\MasterChangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\MasterHistoryReportRequest;
use App\Http\Resources\MasterHistoryResource;
use App\Models\MasterHistory;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterHistoryReportController extends Controller
{
    /**
     * Display the change log of every writer and publisher.
     */
    public function index(MasterHistoryReportRequest $request, MasterHistoryReport $report): Response
    {
        $filters = $request->filters();

        return Inertia::render('reports/master-history', [
            'rows' => MasterHistoryResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => $filters,
            'subjectTypes' => [
                ['value' => 'writer', 'label' => 'Writer'],
                ['value' => 'publisher', 'label' => 'Publisher'],
            ],
            'changeTypes' => MasterChangeType::options(),
            'users' => User::query()
                ->whereIn('id', MasterHistory::query()->whereNotNull('user_id')->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'avatar'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ])
                ->all(),
            'perPageOptions' => MasterHistoryReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the change log as a CSV file.
     */
    public function export(MasterHistoryReportRequest $request, MasterHistoryReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        return $streamCsv(
            'master-history-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($request->filters())->lazy()),
        );
    }
}
