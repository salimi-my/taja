import { Head } from '@inertiajs/react';
import { UserPen, X } from 'lucide-react';
import { useMemo } from 'react';
import { ContentCard } from '@/components/content-card';
import { ReportActions } from '@/components/reports/report-actions';
import { ReportSummaryBar } from '@/components/reports/report-summary-bar';
import { TableFacetedFilter } from '@/components/table/table-faceted-filter';
import { TablePagination } from '@/components/table/table-pagination';
import { TableQueryState } from '@/components/table/table-shell';
import { TableSearchbar } from '@/components/table/table-searchbar';
import { TableSkeleton } from '@/components/table/table-skeleton';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { WorkCatalogueTable } from '@/components/works/work-catalogue-table';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery, reportDefaults } from '@/lib/reports';
import { hasActiveFilters, paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { writerCatalogue } from '@/routes/reports';
import { writerCatalogue as exportWriterCatalogue } from '@/routes/reports/export';
import type {
    EnumOption,
    PaginatedResource,
    ReportWriterOption,
    WorkParty,
} from '@/types';

type Props = {
    rows: PaginatedResource<WorkParty>;
    filters: ServerFilters;
    writer: ReportWriterOption | null;
    writers: ReportWriterOption[];
    roles: EnumOption[];
    registrationStatuses: EnumOption[];
    conflictStatuses: EnumOption[];
};

const DEFAULTS = reportDefaults('effective_start_date.desc');
const REFRESHED_PROPS = ['rows', 'filters', 'writer'];

const INVOLVEMENT_OPTIONS = [
    { id: '0', name: 'Current only' },
    { id: '1', name: 'Include ended' },
];

const toFilterOptions = (options: EnumOption[]) =>
    options.map((item) => ({ id: item.value, name: item.label }));

export default function WriterCatalogueReport({
    rows,
    filters,
    writer,
    writers,
    roles,
    registrationStatuses,
    conflictStatuses,
}: Props) {
    const { params, setParams, isLoading, hasError, retry } = useTableParams({
        url: writerCatalogue().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    const writerOptions = useMemo(
        () =>
            writers.map((item) => ({
                value: String(item.id),
                label: `${item.name} (${item.code})`,
            })),
        [writers],
    );

    const roleOptions = useMemo(() => toFilterOptions(roles), [roles]);
    const registrationOptions = useMemo(
        () => toFilterOptions(registrationStatuses),
        [registrationStatuses],
    );
    const conflictOptions = useMemo(
        () => toFilterOptions(conflictStatuses),
        [conflictStatuses],
    );

    // The writer has its own control, so "Reset" keeps it.
    const isFiltered = hasActiveFilters(
        { ...params, writer_id: undefined },
        DEFAULTS,
    );

    const resetFilters = () =>
        setParams((previous) => ({
            ...DEFAULTS,
            page: 1,
            per_page: previous.per_page,
            sort: previous.sort,
            writer_id: previous.writer_id,
        }));

    return (
        <>
            <Head title="Writer Catalogue" />

            <ContentCard
                title="Writer Catalogue"
                description="Every work a writer is a party of, with the shares, territories and dates."
            >
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex items-center gap-1">
                            <div className="w-64">
                                <Combobox
                                    options={writerOptions}
                                    value={params.writer_id ?? null}
                                    onValueChange={(next) =>
                                        setParams((previous) => ({
                                            ...previous,
                                            page: 1,
                                            writer_id: String(next),
                                        }))
                                    }
                                    placeholder="Select a writer"
                                    searchPlaceholder="Search writers..."
                                    className="h-8"
                                />
                            </div>
                            {params.writer_id && (
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Clear writer"
                                    onClick={() =>
                                        setParams((previous) => ({
                                            ...previous,
                                            page: 1,
                                            writer_id: undefined,
                                        }))
                                    }
                                >
                                    <X />
                                </Button>
                            )}
                        </div>

                        <TableSearchbar params={params} setParams={setParams} />
                        <TableFacetedFilter
                            title="Involvement"
                            column="include_ended"
                            params={params}
                            setParams={setParams}
                            options={INVOLVEMENT_OPTIONS}
                            multiple={false}
                        />
                        <TableFacetedFilter
                            title="Role"
                            column="roles"
                            params={params}
                            setParams={setParams}
                            options={roleOptions}
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
                        {isFiltered && (
                            <Button
                                aria-label="Reset filters"
                                variant="ghost"
                                className="h-8 px-2 lg:px-3"
                                onClick={resetFilters}
                            >
                                Reset
                                <X className="ml-2 size-4" aria-hidden="true" />
                            </Button>
                        )}

                        <div className="ml-auto">
                            <ReportActions
                                disabled={writer === null}
                                exportUrl={
                                    exportWriterCatalogue({
                                        query: exportQuery(params, DEFAULTS),
                                    }).url
                                }
                            />
                        </div>
                    </div>

                    {writer && (
                        <ReportSummaryBar>
                            <UserPen className="size-4 text-muted-foreground" />
                            <span className="font-medium">{writer.name}</span>
                            <span className="font-mono text-xs text-muted-foreground">
                                {writer.code}
                            </span>
                            <span aria-hidden className="text-muted-foreground">
                                ·
                            </span>
                            <span className="text-muted-foreground">
                                {params.include_ended?.[0] === '1'
                                    ? 'Including ended involvement'
                                    : 'Current involvement'}
                            </span>
                        </ReportSummaryBar>
                    )}

                    {writer === null ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <UserPen />
                                </EmptyMedia>
                                <EmptyTitle>Choose a writer</EmptyTitle>
                                <EmptyDescription>
                                    Pick a writer above to see every work they
                                    are a party of.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <TableQueryState
                            isLoading={isLoading}
                            hasError={hasError}
                            data={rows}
                            resourceName="the catalogue"
                            onRetry={retry}
                            skeleton={<TableSkeleton columnCount={12} />}
                        >
                            {(data) =>
                                data.data.length === 0 ? (
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyTitle>No works</EmptyTitle>
                                            <EmptyDescription>
                                                {isFiltered
                                                    ? 'No work matches the search or filters.'
                                                    : 'This writer is not a party of any work yet.'}
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                ) : (
                                    <>
                                        <WorkCatalogueTable rows={data.data} />
                                        <div>
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
                    )}
                </div>
            </ContentCard>
        </>
    );
}

WriterCatalogueReport.layout = {
    breadcrumbs: [
        { title: 'Reports', href: writerCatalogue() },
        { title: 'Writer Catalogue', href: writerCatalogue() },
    ],
};
