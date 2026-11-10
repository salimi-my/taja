import { useMemo } from 'react';
import { TableColumnHeader } from '@/components/table/table-column-header';
import { TableCreateLink } from '@/components/table/table-create-link';
import { TableExportLink } from '@/components/table/table-export-link';
import { TableDateRangeFilter } from '@/components/table/table-date-range-filter';
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
import { WorksCardRow } from '@/components/works/works-card-row';
import { WorksFilterDrawer } from '@/components/works/works-filter-drawer';
import { WorksTableRow } from '@/components/works/works-table-row';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery } from '@/lib/reports';
import { DEFAULT_PARAMS, paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { WORK_COLUMN_LABELS } from '@/lib/works';
import { create, exportMethod, index } from '@/routes/works';
import type { PaginatedResource, Work, WorkFilterOptions } from '@/types';

type WorksTableProps = WorkFilterOptions & {
    works: PaginatedResource<Work>;
    filters: ServerFilters;
};

/** Only these props are refreshed when the table state changes. */
const REFRESHED_PROPS = ['works', 'filters'];

export function WorksTable({
    works,
    filters,
    registrationStatuses,
    conflictStatuses,
    languages,
    genres,
}: WorksTableProps) {
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
        initialParams: paramsFromServer(filters, works.meta.current_page),
        only: REFRESHED_PROPS,
    });

    const exportUrl = exportMethod({
        query: exportQuery(params, DEFAULT_PARAMS),
    }).url;

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
    const languageOptions = useMemo(
        () => languages.map((item) => ({ id: item, name: item })),
        [languages],
    );
    const genreOptions = useMemo(
        () => genres.map((item) => ({ id: item, name: item })),
        [genres],
    );

    const mobileFilterConfigs = useMemo(
        () => [
            {
                key: 'registration_statuses' as const,
                label: 'Registration',
                options: registrationOptions,
            },
            {
                key: 'conflict_statuses' as const,
                label: 'Conflict',
                options: conflictOptions,
            },
            {
                key: 'languages' as const,
                label: 'Language',
                options: languageOptions,
            },
            {
                key: 'genres' as const,
                label: 'Genre',
                options: genreOptions,
            },
        ],
        [registrationOptions, conflictOptions, languageOptions, genreOptions],
    );

    return (
        <TableShell>
            {isMobile ? (
                <TableMobileToolbar
                    params={params}
                    setParams={setParams}
                    filterDrawer={
                        <WorksFilterDrawer
                            params={params}
                            setParams={setParams}
                            registrationOptions={registrationOptions}
                            conflictOptions={conflictOptions}
                            languageOptions={languageOptions}
                            genreOptions={genreOptions}
                        />
                    }
                    filterConfigs={mobileFilterConfigs}
                    columnLabels={WORK_COLUMN_LABELS}
                    actions={
                        <>
                            {isAdmin && (
                                <TableCreateLink
                                    label="Create work"
                                    href={create()}
                                    compact
                                />
                            )}
                            <TableExportLink
                                href={exportUrl}
                                label="Export works as CSV"
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
                                title="Language"
                                column="languages"
                                params={params}
                                setParams={setParams}
                                options={languageOptions}
                            />
                            <TableFacetedFilter
                                title="Genre"
                                column="genres"
                                params={params}
                                setParams={setParams}
                                options={genreOptions}
                            />
                            <TableDateRangeFilter
                                params={params}
                                setParams={setParams}
                            />
                        </>
                    }
                    actions={
                        <>
                            {isAdmin && (
                                <TableCreateLink
                                    label="Create work"
                                    href={create()}
                                />
                            )}
                            <TableExportLink
                                href={exportUrl}
                                label="Export works as CSV"
                            />
                        </>
                    }
                />
            )}

            <TableQueryState
                isLoading={isLoading}
                hasError={hasError}
                data={works}
                resourceName="works"
                onRetry={retry}
                skeleton={
                    <TableSkeleton
                        columnCount={7}
                        cellWidths={[
                            '6rem',
                            '8rem',
                            '14rem',
                            '12rem',
                            '7rem',
                            '7rem',
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
                            <WorksCardRow works={data.data} />
                        ) : (
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
                                                title="MACP Work ID"
                                                column="macp_work_id"
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
                                        <TableHead>Contributors</TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Registration"
                                                column="registration_status"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Registered on"
                                                column="registration_date"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <WorksTableRow works={data.data} />
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
