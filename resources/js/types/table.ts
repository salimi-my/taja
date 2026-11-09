export type FilterOption = {
    id: number | string;
    name: string;
};

/**
 * The query-string state of a datatable. Add the filter keys your table needs
 * here (use `string[]` for faceted filters) and map them in `lib/table.ts`.
 */
export type Params = {
    page?: number;
    per_page?: number;
    search?: string;
    /** Sort in `column.direction` form, e.g. `name.asc`. */
    sort?: string;
    roles?: string[];
    status?: string[];
    /** Import history: the kinds of import (works, writers, publishers). */
    types?: string[];
    societies?: string[];
    publishers?: string[];
    rights_types?: string[];
    territories?: string[];
    registration_statuses?: string[];
    conflict_statuses?: string[];
    languages?: string[];
    genres?: string[];
    controlled?: string[];
    start_date?: string;
    end_date?: string;
    /** Report filters: the chosen writer or work, a point in time and history filters. */
    writer_id?: string;
    work_id?: string;
    /** Writer catalogue: `['1']` includes ended involvement, `['0']` is current only. */
    include_ended?: string[];
    as_of?: string;
    change_types?: string[];
    users?: string[];
    /** Incomplete split report: the kinds of issue a work has. */
    issues?: string[];
    effective_from?: string;
    effective_to?: string;
    recorded_from?: string;
    recorded_to?: string;
};

/** Params keys that hold a list of selected values (faceted filters). */
export type ArrayFilterParamKey = {
    [Key in keyof Params]-?: NonNullable<Params[Key]> extends string[]
        ? Key
        : never;
}[keyof Params];

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};

/** The shape of a paginated Laravel API resource collection. */
export type PaginatedResource<T> = {
    data: T[];
    meta: PaginationMeta;
};
