import type { Dispatch, SetStateAction } from 'react';
import {
    FilterDrawer,
    type FilterGroup,
} from '@/components/table/table-filter-drawer';
import { DEFAULT_PARAMS } from '@/lib/table';
import { PUBLISHER_SORT_COLUMNS } from '@/lib/publishers';
import type { FilterOption, Params } from '@/types';

type PublishersFilterDrawerProps = {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    statusOptions: FilterOption[];
    societyOptions: FilterOption[];
    rightsTypeOptions: FilterOption[];
    territoryOptions: FilterOption[];
};

/**
 * The mobile filter and sort drawer for the publishers table.
 */
export function PublishersFilterDrawer({
    params,
    setParams,
    statusOptions,
    societyOptions,
    rightsTypeOptions,
    territoryOptions,
}: PublishersFilterDrawerProps) {
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
            key: 'rights_types',
            label: 'Rights type',
            options: rightsTypeOptions,
            paramKey: 'rights_types',
        },
        {
            key: 'territories',
            label: 'Territory',
            options: territoryOptions,
            paramKey: 'territories',
        },
    ];

    return (
        <FilterDrawer
            params={params}
            setParams={setParams}
            filterGroups={filterGroups}
            sortColumns={PUBLISHER_SORT_COLUMNS}
            title="Filters & Sort"
            description="Filter and sort the publishers list"
            sortMaxHeight="max-h-[165px]"
            defaultSort={DEFAULT_PARAMS.sort}
        />
    );
}
