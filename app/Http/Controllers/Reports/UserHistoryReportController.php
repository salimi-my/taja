<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\StreamCsv;
use App\Actions\Reports\UserHistoryReport;
use App\Enums\UserChangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\UserHistoryReportRequest;
use App\Http\Resources\UserHistoryResource;
use App\Models\User;
use App\Models\UserHistory;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserHistoryReportController extends Controller
{
    /**
     * Display the change log of every user account.
     */
    public function index(UserHistoryReportRequest $request, UserHistoryReport $report): Response
    {
        $filters = $request->filters();

        return Inertia::render('reports/user-history', [
            'rows' => UserHistoryResource::collection(
                $report->query($filters)->paginate($filters['per_page'])->withQueryString(),
            ),
            'filters' => $filters,
            'changeTypes' => UserChangeType::options(),
            'users' => User::query()
                ->whereIn('id', UserHistory::query()->whereNotNull('actor_id')->select('actor_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'avatar'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ])
                ->all(),
            'perPageOptions' => UserHistoryReportRequest::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Download the change log as a CSV file.
     */
    public function export(UserHistoryReportRequest $request, UserHistoryReport $report, StreamCsv $streamCsv): StreamedResponse
    {
        return $streamCsv(
            'user-history-'.now()->toDateString().'.csv',
            $report->csvHeaders(),
            $report->csvRows($report->query($request->filters())->lazy()),
        );
    }
}
