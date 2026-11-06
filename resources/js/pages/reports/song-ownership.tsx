import { Head, Link } from '@inertiajs/react';
import { Music, X } from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import { formatDate } from '@/components/detail-grid';
import { ReportActions } from '@/components/reports/report-actions';
import { ReportDateFilter } from '@/components/reports/report-date-filter';
import { ReportWorkBadge } from '@/components/reports/report-work-badge';
import { TablePagination } from '@/components/table/table-pagination';
import { TableSearchbar } from '@/components/table/table-searchbar';
import { TableQueryState } from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    WorkConflictBadge,
    WorkRegistrationBadge,
} from '@/components/works/work-badges';
import { useTableParams } from '@/hooks/use-table-params';
import { formatTerritories } from '@/lib/publishers';
import {
    exportQuery,
    formatTotal,
    isCompleteTotal,
    reportDefaults,
} from '@/lib/reports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { formatShare } from '@/lib/works';
import { songOwnership } from '@/routes/reports';
import { songOwnership as exportSongOwnership } from '@/routes/reports/export';
import { show } from '@/routes/works';
import type {
    PaginatedResource,
    ReportWorkFilter,
    WorkOwnershipRow,
} from '@/types';

type Props = {
    rows: PaginatedResource<WorkOwnershipRow>;
    filters: ServerFilters;
    work: ReportWorkFilter | null;
};

const DEFAULTS = reportDefaults('tap_number.asc');
const REFRESHED_PROPS = ['rows', 'filters', 'work'];

function TotalCell({ total }: { total: number | null }) {
    return (
        <TableCell
            className={`text-right font-medium ${isCompleteTotal(total) ? '' : 'text-amber-600 dark:text-amber-400'}`}
        >
            {formatTotal(total)}
        </TableCell>
    );
}

function WorkOwnership({ row }: { row: WorkOwnershipRow }) {
    return (
        <section className="break-inside-avoid space-y-2">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span className="font-mono text-xs">{row.tap_number}</span>
                <span className="font-mono text-xs text-muted-foreground">
                    {row.macp_work_id}
                </span>
                <Link
                    href={show(row.id)}
                    className="link-hover font-semibold after:h-[2px]"
                >
                    {row.title}
                </Link>
                <WorkRegistrationBadge work={row} />
                <WorkConflictBadge work={row} />
            </div>

            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Contributor</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead className="text-right">Perf.</TableHead>
                            <TableHead className="text-right">Mech.</TableHead>
                            <TableHead className="text-right">Sync</TableHead>
                            <TableHead className="text-right">Lyrics</TableHead>
                            <TableHead>Controlled</TableHead>
                            <TableHead>Territories</TableHead>
                            <TableHead>Effective</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {row.parties.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={9}
                                    className="text-center text-muted-foreground"
                                >
                                    No parties in effect.
                                </TableCell>
                            </TableRow>
                        ) : (
                            row.parties.map((party) => (
                                <TableRow key={party.id}>
                                    <TableCell>
                                        {party.contributor_name}
                                        {party.party_code && (
                                            <span className="ml-2 font-mono text-xs text-muted-foreground">
                                                {party.party_code}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {party.role}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatShare(party.performance_share)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatShare(party.mechanical_share)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatShare(party.sync_share)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatShare(party.lyrics_share)}
                                    </TableCell>
                                    <TableCell>
                                        {party.is_controlled ? 'Yes' : 'No'}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatTerritories(
                                            party.territories_controlled,
                                        )}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(party.effective_start_date)}
                                        {' to '}
                                        {formatDate(party.effective_end_date) ??
                                            'present'}
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableCell colSpan={2}>Total</TableCell>
                            <TotalCell total={row.totals.performance} />
                            <TotalCell total={row.totals.mechanical} />
                            <TotalCell total={row.totals.sync} />
                            <TotalCell total={row.totals.lyrics} />
                            <TableCell colSpan={3} />
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>
        </section>
    );
}

export default function SongOwnershipReport({ rows, filters, work }: Props) {
    const { params, setParams, isLoading, hasError, retry } = useTableParams({
        url: songOwnership().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    return (
        <>
            <Head title="Song Ownership Split" />

            <ContentCard
                title="Song Ownership Split"
                description="Who owns each work and the shares they hold, today or as of a date."
            >
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3 print:hidden">
                        <TableSearchbar params={params} setParams={setParams} />
                        <ReportDateFilter
                            label="As of"
                            params={params}
                            setParams={setParams}
                            fromKey="as_of"
                        />
                        {params.as_of && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    setParams((previous) => ({
                                        ...previous,
                                        page: 1,
                                        as_of: undefined,
                                    }))
                                }
                            >
                                Today
                                <X />
                            </Button>
                        )}
                        <ReportWorkBadge work={work} setParams={setParams} />
                        <div className="ml-auto">
                            <ReportActions
                                exportUrl={
                                    exportSongOwnership({
                                        query: exportQuery(params, DEFAULTS),
                                    }).url
                                }
                            />
                        </div>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Ownership{' '}
                        {params.as_of
                            ? `as of ${formatDate(params.as_of)}`
                            : 'today'}
                        . Totals other than 100% are shown in amber.
                    </p>

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
                                            <Music />
                                        </EmptyMedia>
                                        <EmptyTitle>No works found</EmptyTitle>
                                        <EmptyDescription>
                                            No work matches the search.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            ) : (
                                <div className="space-y-6">
                                    {data.data.map((row) => (
                                        <WorkOwnership key={row.id} row={row} />
                                    ))}
                                    <div className="print:hidden">
                                        <TablePagination
                                            total={data.meta.total}
                                            params={params}
                                            setParams={setParams}
                                        />
                                    </div>
                                </div>
                            )
                        }
                    </TableQueryState>
                </div>
            </ContentCard>
        </>
    );
}

SongOwnershipReport.layout = {
    breadcrumbs: [
        { title: 'Reports', href: songOwnership() },
        { title: 'Song Ownership Split', href: songOwnership() },
    ],
};
