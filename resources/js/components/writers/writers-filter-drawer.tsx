import type { Dispatch, SetStateAction } from 'react';
import {
    FilterDrawer,
    type FilterGroup,
} from '@/components/table/table-filter-drawer';
import { DEFAULT_PARAMS } from '@/lib/table';
import { WRITER_SORT_COLUMNS } from '@/lib/writers';
import type { FilterOption, Params } from '@/types';

type WritersFilterDrawerProps = {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    statusOptions: FilterOption[];
    societyOptions: FilterOption[];
    publisherOptions: FilterOption[];
};

/**
 * The mobile filter and sort drawer for the writers table.
 */
export function WritersFilterDrawer({
    params,
    setParams,
    statusOptions,
    societyOptions,
    publisherOptions,
}: WritersFilterDrawerProps) {
    const filterGroups: FilterGroup[] = [
        {
            key: 'status',
            label: 'Status',
            options: statusOptions,
            paramKey: 'status',
        },
        {
            key: 'societies',
            label: 'Society',
            options: societyOptions,
            paramKey: 'societies',
        },
        {
            key: 'publishers',
            label: 'Publisher',
            options: publisherOptions,
            paramKey: 'publishers',
        },
    ];

    return (
        <FilterDrawer
            params={params}
            setParams={setParams}
            filterGroups={filterGroups}
            sortColumns={WRITER_SORT_COLUMNS}
            title="Filters & Sort"
            description="Filter and sort the writers list"
            sortMaxHeight="max-h-[165px]"
            defaultSort={DEFAULT_PARAMS.sort}
        />
    );
}
