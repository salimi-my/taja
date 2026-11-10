import { useMemo } from 'react';
import { TableCreateLink } from '@/components/table/table-create-link';
import { TableExportLink } from '@/components/table/table-export-link';
import { TableColumnHeader } from '@/components/table/table-column-header';
import { TableFacetedFilter } from '@/components/table/table-faceted-filter';
import { TablePagination } from '@/components/table/table-pagination';
import {
    TableDesktopFrame,
    TableDesktopToolbar,
    TableMobileToolbar,
    TableQueryState,
    TableShell,
} from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import {
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { WritersCardRow } from '@/components/writers/writers-card-row';
import { WritersFilterDrawer } from '@/components/writers/writers-filter-drawer';
import { WritersTableRow } from '@/components/writers/writers-table-row';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery } from '@/lib/reports';
import { DEFAULT_PARAMS, paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { WRITER_COLUMN_LABELS } from '@/lib/writers';
import { create, exportMethod, index } from '@/routes/writers';
import type { PaginatedResource, Writer, WriterOptions } from '@/types';

type WritersTableProps = WriterOptions & {
    writers: PaginatedResource<Writer>;
    filters: ServerFilters;
};

/** Only these props are refreshed when the table state changes. */
const REFRESHED_PROPS = ['writers', 'filters'];

export function WritersTable({
    writers,
    filters,
    societies,
    publishers,
    statuses,
}: WritersTableProps) {
    const isMobile = useIsMobile();
    const isAdmin = useIsAdmin();

    const {
        params,
        setParams,
        isFiltered,
        isLoading,
        hasError,
        resetFilters,
        retry,
    } = useTableParams({
        url: index().url,
        initialParams: paramsFromServer(filters, writers.meta.current_page),
        only: REFRESHED_PROPS,
    });

    const exportUrl = exportMethod({
        query: exportQuery(params, DEFAULT_PARAMS),
    }).url;

    const statusOptions = useMemo(
        () => statuses.map((item) => ({ id: item.value, name: item.label })),
        [statuses],
    );
    const societyOptions = useMemo(
        () => societies.map((item) => ({ id: item, name: item })),
        [societies],
    );
    const publisherOptions = useMemo(
        () =>
            publishers.map((item) => ({
                id: String(item.id),
                name: item.name,
            })),
        [publishers],
    );

    const mobileFilterConfigs = useMemo(
        () => [
            { key: 'status' as const, label: 'Status', options: statusOptions },
            {
                key: 'societies' as const,
                label: 'Society',
                options: societyOptions,
            },
            {
                key: 'publishers' as const,
                label: 'Publisher',
                options: publisherOptions,
            },
        ],
        [statusOptions, societyOptions, publisherOptions],
    );

    return (
        <TableShell>
            {isMobile ? (
                <TableMobileToolbar
                    params={params}
                    setParams={setParams}
                    filterDrawer={
                        <WritersFilterDrawer
                            params={params}
                            setParams={setParams}
                            statusOptions={statusOptions}
                            societyOptions={societyOptions}
                            publisherOptions={publisherOptions}
                        />
                    }
                    filterConfigs={mobileFilterConfigs}
                    columnLabels={WRITER_COLUMN_LABELS}
                    actions={
                        <>
                            {isAdmin && (
                                <TableCreateLink
                                    label="Create writer"
                                    href={create()}
                                    compact
                                />
                            )}
                            <TableExportLink
                                href={exportUrl}
                                label="Export writers as CSV"
                                compact
                            />
                        </>
                    }
                />
            ) : (
                <TableDesktopToolbar
                    params={params}
                    setParams={setParams}
                    isFiltered={isFiltered}
                    onResetFilters={resetFilters}
                    filters={
                        <>
                            <TableFacetedFilter
                                title="Status"
                                column="status"
                                params={params}
                                setParams={setParams}
                                options={statusOptions}
                            />
                            <TableFacetedFilter
                                title="Society"
                                column="societies"
                                params={params}
                                setParams={setParams}
                                options={societyOptions}
                            />
                            <TableFacetedFilter
                                title="Publisher"
                                column="publishers"
                                params={params}
                                setParams={setParams}
                                options={publisherOptions}
                            />
                        </>
                    }
                    actions={
                        <>
                            {isAdmin && (
                                <TableCreateLink
                                    label="Create writer"
                                    href={create()}
                                />
                            )}
                            <TableExportLink
                                href={exportUrl}
                                label="Export writers as CSV"
                            />
                        </>
                    }
                />
            )}

            <TableQueryState
                isLoading={isLoading}
                hasError={hasError}
                data={writers}
                resourceName="writers"
                onRetry={retry}
                skeleton={
                    <TableSkeleton
                        columnCount={8}
                        cellWidths={[
                            '6rem',
                            '12rem',
                            '6rem',
                            '8rem',
                            '10rem',
                            '6rem',
                            '8rem',
                            '3rem',
                        ]}
                        shrinkZero
                        mobileCard
                    />
                }
            >
                {(data) => (
                    <>
                        {isMobile ? (
                            <WritersCardRow writers={data.data} />
                        ) : (
                            <TableDesktopFrame>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Code"
                                                column="writer_code"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Name"
                                                column="name"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Society"
                                                column="cmo_society"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>IPI name no.</TableHead>
                                        <TableHead>Publisher</TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Status"
                                                column="status"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Agreement start"
                                                column="agreement_start_date"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <WritersTableRow writers={data.data} />
                                </TableBody>
                            </TableDesktopFrame>
                        )}

                        <TablePagination
                            total={data.meta.total}
                            params={params}
                            setParams={setParams}
                        />
                    </>
                )}
            </TableQueryState>
        </TableShell>
    );
}
