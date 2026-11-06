import { useMemo } from 'react';
import { PublishersCardRow } from '@/components/publishers/publishers-card-row';
import { PublishersFilterDrawer } from '@/components/publishers/publishers-filter-drawer';
import { PublishersTableRow } from '@/components/publishers/publishers-table-row';
import { TableCreateLink } from '@/components/table/table-create-link';
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
import { useIsAdmin } from '@/hooks/use-is-admin';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTableParams } from '@/hooks/use-table-params';
import { PUBLISHER_COLUMN_LABELS } from '@/lib/publishers';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { create, index } from '@/routes/publishers';
import type { PaginatedResource, Publisher, PublisherOptions } from '@/types';

type PublishersTableProps = PublisherOptions & {
    publishers: PaginatedResource<Publisher>;
    filters: ServerFilters;
};

/** Only these props are refreshed when the table state changes. */
const REFRESHED_PROPS = ['publishers', 'filters'];

export function PublishersTable({
    publishers,
    filters,
    societies,
    territories,
    rightsTypes,
    statuses,
}: PublishersTableProps) {
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
        initialParams: paramsFromServer(filters, publishers.meta.current_page),
        only: REFRESHED_PROPS,
    });

    const statusOptions = useMemo(
        () => statuses.map((item) => ({ id: item.value, name: item.label })),
        [statuses],
    );
    const societyOptions = useMemo(
        () => societies.map((item) => ({ id: item, name: item })),
        [societies],
    );
    const rightsTypeOptions = useMemo(
        () => rightsTypes.map((item) => ({ id: item.value, name: item.label })),
        [rightsTypes],
    );
    const territoryOptions = useMemo(
        () => territories.map((item) => ({ id: item, name: item })),
        [territories],
    );
    const rightsTypeLabels = useMemo(
        () =>
            Object.fromEntries(
                rightsTypes.map((item) => [item.value, item.label]),
            ),
        [rightsTypes],
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
                key: 'rights_types' as const,
                label: 'Rights type',
                options: rightsTypeOptions,
            },
            {
                key: 'territories' as const,
                label: 'Territory',
                options: territoryOptions,
            },
        ],
        [statusOptions, societyOptions, rightsTypeOptions, territoryOptions],
    );

    return (
        <TableShell>
            {isMobile ? (
                <TableMobileToolbar
                    params={params}
                    setParams={setParams}
                    filterDrawer={
                        <PublishersFilterDrawer
                            params={params}
                            setParams={setParams}
                            statusOptions={statusOptions}
                            societyOptions={societyOptions}
                            rightsTypeOptions={rightsTypeOptions}
                            territoryOptions={territoryOptions}
                        />
                    }
                    filterConfigs={mobileFilterConfigs}
                    columnLabels={PUBLISHER_COLUMN_LABELS}
                    actions={
                        isAdmin ? (
                            <TableCreateLink
                                label="Create publisher"
                                href={create()}
                                compact
                            />
                        ) : null
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
                                title="Rights type"
                                column="rights_types"
                                params={params}
                                setParams={setParams}
                                options={rightsTypeOptions}
                            />
                            <TableFacetedFilter
                                title="Territory"
                                column="territories"
                                params={params}
                                setParams={setParams}
                                options={territoryOptions}
                            />
                        </>
                    }
                    actions={
                        isAdmin ? (
                            <TableCreateLink
                                label="Create publisher"
                                href={create()}
                            />
                        ) : null
                    }
                />
            )}

            <TableQueryState
                isLoading={isLoading}
                hasError={hasError}
                data={publishers}
                resourceName="publishers"
                onRetry={retry}
                skeleton={
                    <TableSkeleton
                        columnCount={8}
                        cellWidths={[
                            '6rem',
                            '12rem',
                            '6rem',
                            '10rem',
                            '8rem',
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
                            <PublishersCardRow
                                publishers={data.data}
                                rightsTypeLabels={rightsTypeLabels}
                            />
                        ) : (
                            <TableDesktopFrame>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Code"
                                                column="publisher_code"
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
                                                column="society_cmo"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>Rights types</TableHead>
                                        <TableHead>Territories</TableHead>
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
                                    <PublishersTableRow
                                        publishers={data.data}
                                        rightsTypeLabels={rightsTypeLabels}
                                    />
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
