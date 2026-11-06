<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\RightsHistoryReport;
use App\Actions\Reports\StreamCsv;
use App\Enums\RightsChangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\RightsHistoryReportRequest;
use App\Http\Resources\RightsHistoryResource;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RightsHistoryReportController extends Controller
{
    /**
     * Display the change log of every work.
     */
    public function index(RightsHistoryReportRequest $request, RightsHistoryReport $report): Response
    {
        $filters = $request->filters();

        $work = $filters['work_id'] === null ? null : Work::query()->withTrashed()->find($filters['work_id']);

        return Inertia::render('reports/rights-history', [
            'rows' => RightsHistoryResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => $filters,
            'work' => $work === null ? null : [
                'id' => $work->id,
                'tap_number' => $work->tap_number,
                'title' => $work->displayTitle(),
            ],
            'changeTypes' => RightsChangeType::options(),
            'users' => User::query()
                ->whereIn('id', RightsHistory::query()->whereNotNull('user_id')->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
                ->all(),
            'perPageOptions' => RightsHistoryReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the change log as a CSV file.
     */
    public function export(RightsHistoryReportRequest $request, RightsHistoryReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        return $streamCsv(
            'rights-history-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($request->filters())->lazy()),
        );
    }
}
