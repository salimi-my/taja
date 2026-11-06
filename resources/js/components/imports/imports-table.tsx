import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo } from 'react';
import CsvImportBulkDestroyController from '@/actions/App/Http/Controllers/CsvImportBulkDestroyController';
import { ImportsCardRow } from '@/components/imports/imports-card-row';
import { ImportsFilterDrawer } from '@/components/imports/imports-filter-drawer';
import { ImportsTableRow } from '@/components/imports/imports-table-row';
import {
    TableBulkDeleteMultiple,
    TableBulkDeleteMultipleMobile,
} from '@/components/table/table-bulk-delete-multiple';
import { TableColumnHeader } from '@/components/table/table-column-header';
import { TableDateRangeFilter } from '@/components/table/table-date-range-filter';
import { TableFacetedFilter } from '@/components/table/table-faceted-filter';
import { TablePagination } from '@/components/table/table-pagination';
import {
    TableDesktopFrame,
    TableDesktopToolbar,
    TableMobileToolbar,
    TableQueryState,
    TableSelectAllHead,
    TableShell,
} from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import {
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTableParams } from '@/hooks/use-table-params';
import { useTableSelection } from '@/hooks/use-table-selection';
import { IMPORT_COLUMN_LABELS } from '@/lib/imports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/imports';
import type { CsvImportListItem, PaginatedResource } from '@/types';

type ImportsTableProps = {
    csvImports: PaginatedResource<CsvImportListItem>;
    filters: ServerFilters;
    types: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
};

/** Only these props are refreshed when the table state changes. */
const REFRESHED_PROPS = ['csvImports', 'filters'];

const POLL_INTERVAL_MS = 3000;

const BULK_DELETE_DESCRIPTION =
    'The selected imports are removed from the history and their uploaded files are deleted. Records they already created are kept.';

export function ImportsTable({
    csvImports,
    filters,
    types,
    statuses,
}: ImportsTableProps) {
    const isMobile = useIsMobile();

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
        initialParams: paramsFromServer(filters, csvImports.meta.current_page),
        only: REFRESHED_PROPS,
    });

    const typeOptions = useMemo(
        () => types.map((item) => ({ id: item.value, name: item.label })),
        [types],
    );
    const statusOptions = useMemo(
        () => statuses.map((item) => ({ id: item.value, name: item.label })),
        [statuses],
    );

    const pageImportIds = useMemo(
        () => csvImports.data.map((csvImport) => csvImport.id),
        [csvImports.data],
    );

    const deletableImportIds = useMemo(
        () =>
            new Set(
                csvImports.data
                    .filter((csvImport) => csvImport.can_delete)
                    .map((csvImport) => csvImport.id),
            ),
        [csvImports.data],
    );

    const isImportSelectable = useCallback(
        (id: number) => deletableImportIds.has(id),
        [deletableImportIds],
    );

    const {
        selectedIds,
        setSelectedIds,
        handleToggleSelect,
        handleToggleSelectAll,
        selectAllChecked,
        selectableCount,
    } = useTableSelection({
        params,
        pageIds: pageImportIds,
        isSelectable: isImportSelectable,
    });

    const bulkDeleteUrl = CsvImportBulkDestroyController.url();

    const mobileFilterConfigs = useMemo(
        () => [
            { key: 'types' as const, label: 'Type', options: typeOptions },
            { key: 'status' as const, label: 'Status', options: statusOptions },
        ],
        [typeOptions, statusOptions],
    );

    const hasProcessingImport = csvImports.data.some(
        (csvImport) => csvImport.is_processing,
    );

    useEffect(() => {
        if (!hasProcessingImport) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: REFRESHED_PROPS });
        }, POLL_INTERVAL_MS);

        return () => window.clearInterval(timer);
    }, [hasProcessingImport]);

    return (
        <TableShell>
            {isMobile ? (
                <TableMobileToolbar
                    params={params}
                    setParams={setParams}
                    filterDrawer={
                        <ImportsFilterDrawer
                            params={params}
                            setParams={setParams}
                            typeOptions={typeOptions}
                            statusOptions={statusOptions}
                        />
                    }
                    filterConfigs={mobileFilterConfigs}
                    columnLabels={IMPORT_COLUMN_LABELS}
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
                                title="Type"
                                column="types"
                                params={params}
                                setParams={setParams}
                                options={typeOptions}
                            />
                            <TableFacetedFilter
                                title="Status"
                                column="status"
                                params={params}
                                setParams={setParams}
                                options={statusOptions}
                            />
                            <TableDateRangeFilter
                                params={params}
                                setParams={setParams}
                            />
                        </>
                    }
                    actions={
                        selectedIds.size > 0 ? (
                            <TableBulkDeleteMultiple
                                selectedIds={selectedIds}
                                setSelectedIds={setSelectedIds}
                                endpoint={bulkDeleteUrl}
                                entityName="import"
                                description={BULK_DELETE_DESCRIPTION}
                            />
                        ) : null
                    }
                />
            )}

            <TableQueryState
                isLoading={isLoading}
                hasError={hasError}
                data={csvImports}
                resourceName="imports"
                onRetry={retry}
                skeleton={
                    <TableSkeleton
                        columnCount={10}
                        cellWidths={[
                            '2.5rem',
                            '14rem',
                            '6rem',
                            '6rem',
                            '4rem',
                            '4rem',
                            '4rem',
                            '8rem',
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
                            <>
                                <ImportsCardRow
                                    csvImports={data.data}
                                    selectedIds={selectedIds}
                                    handleToggleSelect={handleToggleSelect}
                                />
                                <TableBulkDeleteMultipleMobile
                                    selectedIds={selectedIds}
                                    setSelectedIds={setSelectedIds}
                                    endpoint={bulkDeleteUrl}
                                    entityName="import"
                                    description={BULK_DELETE_DESCRIPTION}
                                    selectableCount={selectableCount}
                                    selectAllAriaLabel="Select all imports"
                                    onToggleSelectAll={handleToggleSelectAll}
                                />
                            </>
                        ) : (
                            <TableDesktopFrame>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-8">
                                            <TableSelectAllHead
                                                ariaLabel="Select all imports"
                                                checked={selectAllChecked}
                                                onCheckedChange={
                                                    handleToggleSelectAll
                                                }
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="File"
                                                column="original_filename"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Type"
                                                column="type"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
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
                                                title="Rows"
                                                column="total_rows"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>Valid</TableHead>
                                        <TableHead>Errors</TableHead>
                                        <TableHead>Uploaded by</TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Date"
                                                column="created_at"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <ImportsTableRow
                                        csvImports={data.data}
                                        selectedIds={selectedIds}
                                        handleToggleSelect={handleToggleSelect}
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
