import type { Params } from '@/types';

/** Per-page options shown in the pagination footer. Mirrors the server. */
export const PER_PAGE_OPTIONS = [10, 25, 50, 100] as const;

export const DEFAULT_PARAMS: Params = {
    page: 1,
    per_page: 10,
    sort: 'created_at.desc',
    search: undefined,
    roles: undefined,
    status: undefined,
    start_date: undefined,
    end_date: undefined,
};

/** The filters the server echoes back for a table (see `UserController`). */
export type ServerFilters = {
    search: string;
    roles: string[];
    status: string[];
    start_date: string;
    end_date: string;
    sort: string;
    direction: string;
    per_page: number;
};

/** Build the initial client params from the filters the server applied. */
export function paramsFromServer(
    filters: ServerFilters,
    currentPage: number,
): Params {
    return {
        page: currentPage,
        per_page: filters.per_page,
        search: filters.search || undefined,
        sort: `${filters.sort}.${filters.direction}`,
        roles: filters.roles.length > 0 ? filters.roles : undefined,
        status: filters.status.length > 0 ? filters.status : undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    };
}

/** Turn client params into the query string the server understands. */
export function paramsToQuery(
    params: Params,
    defaultParams: Params = DEFAULT_PARAMS,
): Record<string, string | number | string[]> {
    const query: Record<string, string | number | string[]> = {};

    if (params.page && params.page > 1) {
        query.page = params.page;
    }

    if (params.per_page && params.per_page !== defaultParams.per_page) {
        query.per_page = params.per_page;
    }

    if (params.search) {
        query.search = params.search;
    }

    if (params.roles?.length) {
        query.roles = params.roles;
    }

    if (params.status?.length) {
        query.status = params.status;
    }

    if (params.start_date) {
        query.start_date = params.start_date;
    }

    if (params.end_date) {
        query.end_date = params.end_date;
    }

    if (params.sort && params.sort !== defaultParams.sort) {
        const [column, direction] = params.sort.split('.');

        query.sort = column;
        query.direction = direction;
    }

    return query;
}

/**
 * Whether any filter (excluding sort and pagination) differs from the
 * defaults, used to decide if the "Reset" button should be shown.
 */
export function hasActiveFilters(
    params: Params,
    defaultParams: Params = DEFAULT_PARAMS,
): boolean {
    const keys: (keyof Params)[] = [
        'search',
        'roles',
        'status',
        'start_date',
        'end_date',
    ];

    return keys.some(
        (key) =>
            JSON.stringify(params[key] ?? null) !==
            JSON.stringify(defaultParams[key] ?? null),
    );
}

/** Compare params regardless of key order, ignoring undefined values. */
export function paramsEqual(first: Params, second: Params): boolean {
    const normalize = (params: Params): string =>
        JSON.stringify(
            Object.entries(params)
                .filter(([, value]) => value !== undefined)
                .sort(([firstKey], [secondKey]) =>
                    firstKey.localeCompare(secondKey),
                ),
        );

    return normalize(first) === normalize(second);
}
