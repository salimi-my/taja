import type { Dispatch, SetStateAction } from 'react';
import {
    FilterDrawer,
    type FilterGroup,
} from '@/components/table/table-filter-drawer';
import { DEFAULT_PARAMS } from '@/lib/table';
import { WORK_SORT_COLUMNS, YES_NO_FILTER_OPTIONS } from '@/lib/works';
import type { FilterOption, Params } from '@/types';

type WorksFilterDrawerProps = {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    registrationOptions: FilterOption[];
    conflictOptions: FilterOption[];
    workTypeOptions: FilterOption[];
    languageOptions: FilterOption[];
    genreOptions: FilterOption[];
};

/**
 * The mobile filter and sort drawer for the works table.
 */
export function WorksFilterDrawer({
    params,
    setParams,
    registrationOptions,
    conflictOptions,
    workTypeOptions,
    languageOptions,
    genreOptions,
}: WorksFilterDrawerProps) {
    const filterGroups: FilterGroup[] = [
        {
            key: 'registration_statuses',
            label: 'Registration',
            options: registrationOptions,
            paramKey: 'registration_statuses',
        },
        {
            key: 'conflict_statuses',
            label: 'Conflict',
            options: conflictOptions,
            paramKey: 'conflict_statuses',
        },
        {
            key: 'work_types',
            label: 'Work type',
            options: workTypeOptions,
            paramKey: 'work_types',
        },
        {
            key: 'languages',
            label: 'Language',
            options: languageOptions,
            paramKey: 'languages',
        },
        {
            key: 'genres',
            label: 'Genre',
            options: genreOptions,
            paramKey: 'genres',
        },
        {
            key: 'public_domain',
            label: 'Public domain',
            options: YES_NO_FILTER_OPTIONS,
            paramKey: 'public_domain',
        },
        {
            key: 'controlled',
            label: 'Controlled',
            options: YES_NO_FILTER_OPTIONS,
            paramKey: 'controlled',
        },
    ];

    return (
        <FilterDrawer
            params={params}
            setParams={setParams}
            filterGroups={filterGroups}
            sortColumns={WORK_SORT_COLUMNS}
            title="Filters & Sort"
            description="Filter and sort the works list"
            sortMaxHeight="max-h-[165px]"
            defaultSort={DEFAULT_PARAMS.sort}
        />
    );
}
