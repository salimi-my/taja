import type { Dispatch, SetStateAction } from 'react';
import {
    FilterDrawer,
    type FilterGroup,
} from '@/components/table/table-filter-drawer';
import { IMPORT_SORT_COLUMNS } from '@/lib/imports';
import { DEFAULT_PARAMS } from '@/lib/table';
import type { FilterOption, Params } from '@/types';

type ImportsFilterDrawerProps = {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    typeOptions: FilterOption[];
    statusOptions: FilterOption[];
};

/**
 * The mobile filter and sort drawer for the import history.
 */
export function ImportsFilterDrawer({
    params,
    setParams,
    typeOptions,
    statusOptions,
}: ImportsFilterDrawerProps) {
    const filterGroups: FilterGroup[] = [
        {
            key: 'types',
            label: 'Type',
            options: typeOptions,
            paramKey: 'types',
        },
        {
            key: 'status',
            label: 'Status',
            options: statusOptions,
            paramKey: 'status',
        },
    ];

    return (
        <FilterDrawer
            params={params}
            setParams={setParams}
            filterGroups={filterGroups}
            sortColumns={IMPORT_SORT_COLUMNS}
            title="Filters & Sort"
            description="Filter and sort the import history"
            sortMaxHeight="max-h-[165px]"
            defaultSort={DEFAULT_PARAMS.sort}
        />
    );
}
