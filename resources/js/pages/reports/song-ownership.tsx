import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { CalendarDays, Music, TriangleAlert, Users } from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import { formatDate } from '@/components/detail-grid';
import { ReportActions } from '@/components/reports/report-actions';
import { ReportDateFilter } from '@/components/reports/report-date-filter';
import { ReportSummaryBar } from '@/components/reports/report-summary-bar';
import { ReportWorkBadge } from '@/components/reports/report-work-badge';
import { ShareTotalCell } from '@/components/reports/share-total-cell';
import { TableFacetedFilter } from '@/components/table/table-faceted-filter';
import { TablePagination } from '@/components/table/table-pagination';
import {
    TableDesktopToolbar,
    TableQueryState,
} from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import { Badge } from '@/components/ui/badge';
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
import { WorkStatuses } from '@/components/works/work-badges';
import {
    PartyPeriodCell,
    PartyTerritoriesCell,
    ShareCell,
} from '@/components/works/party-cells';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery, isCompleteTotal, reportDefaults } from '@/lib/reports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { YES_NO_FILTER_OPTIONS } from '@/lib/works';
import { songOwnership } from '@/routes/reports';
import { songOwnership as exportSongOwnership } from '@/routes/reports/export';
import { show } from '@/routes/works';
import type {
    EnumOption,
    PaginatedResource,
    ReportWorkFilter,
    WorkOwnershipRow,
} from '@/types';

type Props = {
    rows: PaginatedResource<WorkOwnershipRow>;
    filters: ServerFilters;
    work: ReportWorkFilter | null;
    registrationStatuses: EnumOption[];
    conflictStatuses: EnumOption[];
};

const DEFAULTS = reportDefaults('tap_number.asc');
const REFRESHED_PROPS = ['rows', 'filters', 'work'];

const SHARE_COLUMNS = [
    {
        label: 'Perf.',
        title: 'Performance',
        key: 'performance_share',
        total: 'performance',
    },
    {
        label: 'Mech.',
        title: 'Mechanical',
        key: 'mechanical_share',
        total: 'mechanical',
    },
    { label: 'Sync', title: 'Sync', key: 'sync_share', total: 'sync' },
    { label: 'Lyrics', title: 'Lyrics', key: 'lyrics_share', total: 'lyrics' },
] as const;

function WorkOwnership({ row }: { row: WorkOwnershipRow }) {
    const hasIncompleteSplit = !Object.values(row.totals).every(
        isCompleteTotal,
    );

    return (
        <section className="break-inside-avoid overflow-hidden rounded-md border bg-card">
            <header className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b bg-muted/40 px-4 py-3">
                <div className="min-w-0">
                    <Link
                        href={show(row.id)}
                        className="link-hover font-semibold after:h-[2px]"
                    >
                        {row.title}
                    </Link>
                    <p className="mt-0.5 flex flex-wrap items-center gap-x-2 font-mono text-xs text-muted-foreground">
                        <span>{row.tap_number}</span>
                        <span aria-hidden>·</span>
                        <span>{row.macp_work_id}</span>
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {hasIncompleteSplit && (
                        <Badge
                            variant="outline"
                            className="gap-1 border-amber-500 text-amber-600 dark:border-amber-400 dark:text-amber-400"
                        >
                            <TriangleAlert />
                            Incomplete split
                        </Badge>
                    )}
                    <WorkStatuses work={row} />
                </div>
            </header>

            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        <TableHead>Contributor</TableHead>
                        {SHARE_COLUMNS.map((column) => (
                            <TableHead
                                key={column.key}
                                title={column.title}
                                className="text-right"
                            >
                                {column.label}
                            </TableHead>
                        ))}
                        <TableHead>Territories</TableHead>
                        <TableHead>Effective</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {row.parties.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={7}>
                                <Empty className="border-0 p-4">
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Users />
                                        </EmptyMedia>
                                        <EmptyTitle>
                                            No parties in effect
                                        </EmptyTitle>
                                    </EmptyHeader>
                                </Empty>
                            </TableCell>
                        </TableRow>
                    ) : (
                        row.parties.map((party) => (
                            <TableRow key={party.id}>
                                <TableCell>
                                    <div className="font-medium">
                                        {party.contributor_name}
                                    </div>
                                    <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                        <span>{party.role_label}</span>
                                        {party.party_code && (
                                            <span className="font-mono">
                                                {party.party_code}
                                            </span>
                                        )}
                                    </div>
                                </TableCell>
                                {SHARE_COLUMNS.map((column) => (
                                    <ShareCell
                                        key={column.key}
                                        value={party[column.key]}
                                    />
                                ))}
                                <PartyTerritoriesCell party={party} />
                                <PartyPeriodCell party={party} />
                            </TableRow>
                        ))
                    )}
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell className="font-semibold">Total</TableCell>
                        {SHARE_COLUMNS.map((column) => (
                            <ShareTotalCell
                                key={column.total}
                                total={row.totals[column.total]}
                            />
                        ))}
                        <TableCell colSpan={2} />
                    </TableRow>
                </TableFooter>
            </Table>
        </section>
    );
}

export default function SongOwnershipReport({
    rows,
    filters,
    work,
    registrationStatuses,
    conflictStatuses,
}: Props) {
    const {
        params,
        setParams,
        isFiltered,
        isLoading,
        hasError,
        resetFilters,
        retry,
    } = useTableParams({
        url: songOwnership().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    const registrationOptions = useMemo(
        () =>
            registrationStatuses.map((item) => ({
                id: item.value,
                name: item.label,
            })),
        [registrationStatuses],
    );
    const conflictOptions = useMemo(
        () =>
            conflictStatuses.map((item) => ({
                id: item.value,
                name: item.label,
            })),
        [conflictStatuses],
    );

    return (
        <>
            <Head title="Song Ownership Split" />

            <ContentCard
                title="Song Ownership Split"
                description="Who owns each work and the shares they hold, today or as of a date."
            >
                <div className="space-y-4">
                    <div>
                        <TableDesktopToolbar
                            params={params}
                            setParams={setParams}
                            isFiltered={isFiltered}
                            onResetFilters={resetFilters}
                            filters={
                                <>
                                    <ReportDateFilter
                                        label="As of"
                                        params={params}
                                        setParams={setParams}
                                        paramKey="as_of"
                                    />
                                    <ReportWorkBadge
                                        work={work}
                                        setParams={setParams}
                                    />
                                    <TableFacetedFilter
                                        title="Registration"
                                        column="registration_statuses"
                                        params={params}
                                        setParams={setParams}
                                        options={registrationOptions}
                                    />
                                    <TableFacetedFilter
                                        title="Conflict"
                                        column="conflict_statuses"
                                        params={params}
                                        setParams={setParams}
                                        options={conflictOptions}
                                    />
                                    <TableFacetedFilter
                                        title="Controlled"
                                        column="controlled"
                                        params={params}
                                        setParams={setParams}
                                        options={YES_NO_FILTER_OPTIONS}
                                    />
                                </>
                            }
                            actions={
                                <ReportActions
                                    exportUrl={
                                        exportSongOwnership({
                                            query: exportQuery(
                                                params,
                                                DEFAULTS,
                                            ),
                                        }).url
                                    }
                                />
                            }
                        />
                    </div>

                    <ReportSummaryBar note="A total other than 100% means the split is incomplete.">
                        <CalendarDays className="size-4 text-muted-foreground" />
                        <span className="text-muted-foreground">Ownership</span>
                        <span className="font-medium">
                            {params.as_of
                                ? `as of ${formatDate(params.as_of)}`
                                : 'today'}
                        </span>
                        <span aria-hidden className="text-muted-foreground">
                            ·
                        </span>
                        <span className="text-muted-foreground">
                            {rows.meta.total}{' '}
                            {rows.meta.total === 1 ? 'work' : 'works'}
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
                                            <Music />
                                        </EmptyMedia>
                                        <EmptyTitle>No works found</EmptyTitle>
                                        <EmptyDescription>
                                            No work matches the search or
                                            filters.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            ) : (
                                <div className="space-y-6">
                                    {data.data.map((row) => (
                                        <WorkOwnership key={row.id} row={row} />
                                    ))}
                                    <div>
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
