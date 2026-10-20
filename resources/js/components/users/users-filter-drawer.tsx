import type { Dispatch, SetStateAction } from 'react';
import {
    FilterDrawer,
    type FilterGroup,
} from '@/components/table/table-filter-drawer';
import { DEFAULT_PARAMS } from '@/lib/table';
import { STATUS_OPTIONS, USER_SORT_COLUMNS } from '@/lib/users';
import type { FilterOption, Params } from '@/types';

type UsersFilterDrawerProps = {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    roleOptions: FilterOption[];
};

/**
 * The mobile filter and sort drawer for the users table.
 */
export function UsersFilterDrawer({
    params,
    setParams,
    roleOptions,
}: UsersFilterDrawerProps) {
    const filterGroups: FilterGroup[] = [
        {
            key: 'roles',
            label: 'Roles',
            options: roleOptions,
            paramKey: 'roles',
        },
        {
            key: 'status',
            label: 'Status',
            options: STATUS_OPTIONS,
            paramKey: 'status',
        },
    ];

    return (
        <FilterDrawer
            params={params}
            setParams={setParams}
            filterGroups={filterGroups}
            sortColumns={USER_SORT_COLUMNS}
            title="Filters & Sort"
            description="Filter and sort the users list"
            sortMaxHeight="max-h-[165px]"
            defaultSort={DEFAULT_PARAMS.sort}
        />
    );
}
