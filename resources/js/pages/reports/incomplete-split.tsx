import { Head, Link } from '@inertiajs/react';
import { CircleCheck, ListChecks, TriangleAlert } from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import { ReportActions } from '@/components/reports/report-actions';
import { ReportSummaryBar } from '@/components/reports/report-summary-bar';
import { ShareTotalCell } from '@/components/reports/share-total-cell';
import { TableColumnHeader } from '@/components/table/table-column-header';
import { TablePagination } from '@/components/table/table-pagination';
import { TableSearchbar } from '@/components/table/table-searchbar';
import {
    TableDesktopFrame,
    TableQueryState,
} from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { WorkRegistrationBadge } from '@/components/works/work-badges';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery, reportDefaults } from '@/lib/reports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { incompleteSplit } from '@/routes/reports';
import { incompleteSplit as exportIncompleteSplit } from '@/routes/reports/export';
import { show } from '@/routes/works';
import type { IncompleteSplitRow, PaginatedResource } from '@/types';

type Props = {
    rows: PaginatedResource<IncompleteSplitRow>;
    filters: ServerFilters;
};

const DEFAULTS = reportDefaults('tap_number.asc');
const REFRESHED_PROPS = ['rows', 'filters'];

export default function IncompleteSplitReport({ rows, filters }: Props) {
    const { params, setParams, isLoading, hasError, retry } = useTableParams({
        url: incompleteSplit().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    return (
        <>
            <Head title="Incomplete Split" />

            <ContentCard
                title="Incomplete Split"
                description="Works that have no parties in effect, or whose shares do not add up to 100%."
            >
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3 print:hidden">
                        <TableSearchbar params={params} setParams={setParams} />
                        <div className="ml-auto">
                            <ReportActions
                                exportUrl={
                                    exportIncompleteSplit({
                                        query: exportQuery(params, DEFAULTS),
                                    }).url
                                }
                            />
                        </div>
                    </div>

                    <ReportSummaryBar note="Performance and mechanical are always checked. Sync and lyrics only when a party has a value.">
                        <ListChecks className="size-4 text-muted-foreground" />
                        <span className="text-muted-foreground">
                            Totals over the parties in effect today
                        </span>
                        <span aria-hidden className="text-muted-foreground">
                            ·
                        </span>
                        <span className="font-medium">
                            {rows.meta.total}{' '}
                            {rows.meta.total === 1 ? 'work' : 'works'} to fix
                        </span>
                    </ReportSummaryBar>

                    <TableQueryState
                        isLoading={isLoading}
                        hasError={hasError}
                        data={rows}
                        resourceName="the report"
                        onRetry={retry}
                        skeleton={<TableSkeleton columnCount={9} />}
                    >
                        {(data) =>
                            data.data.length === 0 ? (
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <CircleCheck />
                                        </EmptyMedia>
                                        <EmptyTitle>
                                            No incomplete splits
                                        </EmptyTitle>
                                        <EmptyDescription>
                                            Every work has parties and its
                                            shares add up to 100%.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            ) : (
                                <>
                                    <TableDesktopFrame>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    <TableColumnHeader
                                                        title="TAP"
                                                        column="tap_number"
                                                        params={params}
                                                        setParams={setParams}
                                                    />
                                                </TableHead>
                                                <TableHead>
                                                    <TableColumnHeader
                                                        title="Title"
                                                        column="english_title"
                                                        params={params}
                                                        setParams={setParams}
                                                    />
                                                </TableHead>
                                                <TableHead>
                                                    <TableColumnHeader
                                                        title="Registration"
                                                        column="registration_status"
                                                        params={params}
                                                        setParams={setParams}
                                                    />
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Parties
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Perf.
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Mech.
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Sync
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Lyrics
                                                </TableHead>
                                                <TableHead>Issues</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {data.data.map((row) => (
                                                <TableRow key={row.id}>
                                                    <TableCell className="font-mono text-xs">
                                                        {row.tap_number}
                                                    </TableCell>
                                                    <TableCell className="max-w-[14rem]">
                                                        <Link
                                                            href={show(row.id)}
                                                            className="link-hover block truncate font-semibold after:h-[2px]"
                                                        >
                                                            {row.title}
                                                        </Link>
                                                        <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                            {row.macp_work_id}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell>
                                                        <WorkRegistrationBadge
                                                            work={row}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {row.parties_count ===
                                                        0 ? (
                                                            <span className="font-medium text-amber-600 dark:text-amber-400">
                                                                0
                                                            </span>
                                                        ) : (
                                                            row.parties_count
                                                        )}
                                                    </TableCell>
                                                    <ShareTotalCell
                                                        total={
                                                            row.totals
                                                                .performance
                                                        }
                                                        hasParties={
                                                            row.parties_count >
                                                            0
                                                        }
                                                    />
                                                    <ShareTotalCell
                                                        total={
                                                            row.totals
                                                                .mechanical
                                                        }
                                                        hasParties={
                                                            row.parties_count >
                                                            0
                                                        }
                                                    />
                                                    <ShareTotalCell
                                                        total={row.totals.sync}
                                                        hasParties={
                                                            row.parties_count >
                                                            0
                                                        }
                                                    />
                                                    <ShareTotalCell
                                                        total={
                                                            row.totals.lyrics
                                                        }
                                                        hasParties={
                                                            row.parties_count >
                                                            0
                                                        }
                                                    />
                                                    <TableCell className="min-w-[13rem] whitespace-normal">
                                                        <ul className="space-y-1">
                                                            {row.reasons.map(
                                                                (reason) => (
                                                                    <li
                                                                        key={
                                                                            reason
                                                                        }
                                                                        className="flex items-start gap-1.5 text-xs"
                                                                    >
                                                                        <TriangleAlert className="mt-0.5 size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                                                        {reason}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </TableDesktopFrame>
                                    <div className="print:hidden">
                                        <TablePagination
                                            total={data.meta.total}
                                            params={params}
                                            setParams={setParams}
                                        />
                                    </div>
                                </>
                            )
                        }
                    </TableQueryState>
                </div>
            </ContentCard>
        </>
    );
}

IncompleteSplitReport.layout = {
    breadcrumbs: [
        { title: 'Reports', href: incompleteSplit() },
        { title: 'Incomplete Split', href: incompleteSplit() },
    ],
};
