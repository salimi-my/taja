import { useCallback, useMemo } from 'react';
import UserBulkDestroyController from '@/actions/App/Http/Controllers/UserBulkDestroyController';
import {
    TableBulkDeleteMultiple,
    TableBulkDeleteMultipleMobile,
} from '@/components/table/table-bulk-delete-multiple';
import { TableColumnHeader } from '@/components/table/table-column-header';
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
import { UserCreate } from '@/components/users/user-create';
import { UsersCardRow } from '@/components/users/users-card-row';
import { UsersFilterDrawer } from '@/components/users/users-filter-drawer';
import { UsersTableRow } from '@/components/users/users-table-row';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTableParams } from '@/hooks/use-table-params';
import { useTableSelection } from '@/hooks/use-table-selection';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { STATUS_OPTIONS, USER_COLUMN_LABELS } from '@/lib/users';
import { index } from '@/routes/users';
import type { ManagedUser, PaginatedResource, RoleOption } from '@/types';

type UsersTableProps = {
    users: PaginatedResource<ManagedUser>;
    filters: ServerFilters;
    roles: RoleOption[];
};

/** Only these props are refreshed when the table state changes. */
const REFRESHED_PROPS = ['users', 'filters'];

export function UsersTable({ users, filters, roles }: UsersTableProps) {
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
        initialParams: paramsFromServer(filters, users.meta.current_page),
        only: REFRESHED_PROPS,
    });

    const roleOptions = useMemo(
        () => roles.map((role) => ({ id: role.value, name: role.label })),
        [roles],
    );

    const pageUserIds = useMemo(
        () => users.data.map((user) => user.id),
        [users.data],
    );

    const selectableUserIds = useMemo(
        () =>
            new Set(
                users.data
                    .filter((user) => !user.is_current_user)
                    .map((user) => user.id),
            ),
        [users.data],
    );

    const isUserSelectable = useCallback(
        (id: number) => selectableUserIds.has(id),
        [selectableUserIds],
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
        pageIds: pageUserIds,
        isSelectable: isUserSelectable,
    });

    const mobileFilterConfigs = useMemo(
        () => [
            { key: 'roles' as const, label: 'Role', options: roleOptions },
            {
                key: 'status' as const,
                label: 'Status',
                options: STATUS_OPTIONS,
            },
        ],
        [roleOptions],
    );

    const bulkDeleteUrl = UserBulkDestroyController.url();

    return (
        <TableShell>
            {isMobile ? (
                <TableMobileToolbar
                    params={params}
                    setParams={setParams}
                    filterDrawer={
                        <UsersFilterDrawer
                            params={params}
                            setParams={setParams}
                            roleOptions={roleOptions}
                        />
                    }
                    filterConfigs={mobileFilterConfigs}
                    columnLabels={USER_COLUMN_LABELS}
                    actions={<UserCreate roles={roles} compact />}
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
                                title="Roles"
                                column="roles"
                                params={params}
                                setParams={setParams}
                                options={roleOptions}
                            />
                            <TableFacetedFilter
                                title="Status"
                                column="status"
                                params={params}
                                setParams={setParams}
                                options={STATUS_OPTIONS}
                            />
                        </>
                    }
                    actions={
                        <>
                            {selectedIds.size > 0 && (
                                <TableBulkDeleteMultiple
                                    selectedIds={selectedIds}
                                    setSelectedIds={setSelectedIds}
                                    endpoint={bulkDeleteUrl}
                                    entityName="user"
                                />
                            )}
                            <UserCreate roles={roles} />
                        </>
                    }
                />
            )}

            <TableQueryState
                isLoading={isLoading}
                hasError={hasError}
                data={users}
                resourceName="users"
                onRetry={retry}
                skeleton={
                    <TableSkeleton
                        columnCount={7}
                        cellWidths={[
                            '2.5rem',
                            '12rem',
                            '12rem',
                            '8rem',
                            '8rem',
                            '8rem',
                            '8rem',
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
                                <UsersCardRow
                                    users={data.data}
                                    roles={roles}
                                    selectedIds={selectedIds}
                                    handleToggleSelect={handleToggleSelect}
                                />
                                <TableBulkDeleteMultipleMobile
                                    selectedIds={selectedIds}
                                    setSelectedIds={setSelectedIds}
                                    endpoint={bulkDeleteUrl}
                                    entityName="user"
                                    selectableCount={selectableCount}
                                    selectAllAriaLabel="Select all users"
                                    onToggleSelectAll={handleToggleSelectAll}
                                />
                            </>
                        ) : (
                            <TableDesktopFrame>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-8">
                                            <TableSelectAllHead
                                                ariaLabel="Select all users"
                                                checked={selectAllChecked}
                                                onCheckedChange={
                                                    handleToggleSelectAll
                                                }
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
                                                title="Email"
                                                column="email"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Role"
                                                column="role"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Verified"
                                                column="email_verified_at"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead>
                                            <TableColumnHeader
                                                title="Created Date"
                                                column="created_at"
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <UsersTableRow
                                        users={data.data}
                                        roles={roles}
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
