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
    start_date?: string;
    end_date?: string;
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
