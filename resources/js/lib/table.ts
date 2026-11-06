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
    types: undefined,
    societies: undefined,
    publishers: undefined,
    rights_types: undefined,
    territories: undefined,
    registration_statuses: undefined,
    conflict_statuses: undefined,
    work_types: undefined,
    languages: undefined,
    genres: undefined,
    public_domain: undefined,
    controlled: undefined,
    start_date: undefined,
    end_date: undefined,
    writer_id: undefined,
    work_id: undefined,
    include_ended: undefined,
    as_of: undefined,
    effective_from: undefined,
    effective_to: undefined,
    recorded_from: undefined,
    recorded_to: undefined,
    change_types: undefined,
    users: undefined,
};

/** The filters the server echoes back for a table (see `UserController`). */
export type ServerFilters = {
    search: string;
    roles?: string[];
    status?: string[];
    types?: string[];
    societies?: string[];
    publishers?: string[];
    rights_types?: string[];
    territories?: string[];
    registration_statuses?: string[];
    conflict_statuses?: string[];
    work_types?: string[];
    languages?: string[];
    genres?: string[];
    public_domain?: string[];
    controlled?: string[];
    start_date?: string;
    end_date?: string;
    as_of?: string;
    effective_from?: string;
    effective_to?: string;
    recorded_from?: string;
    recorded_to?: string;
    writer_id?: number | null;
    work_id?: number | null;
    include_ended?: boolean;
    change_types?: string[];
    users?: string[];
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
        roles: filters.roles?.length ? filters.roles : undefined,
        status: filters.status?.length ? filters.status : undefined,
        types: filters.types?.length ? filters.types : undefined,
        societies: filters.societies?.length ? filters.societies : undefined,
        publishers: filters.publishers?.length ? filters.publishers : undefined,
        rights_types: filters.rights_types?.length
            ? filters.rights_types
            : undefined,
        territories: filters.territories?.length
            ? filters.territories
            : undefined,
        registration_statuses: filters.registration_statuses?.length
            ? filters.registration_statuses
            : undefined,
        conflict_statuses: filters.conflict_statuses?.length
            ? filters.conflict_statuses
            : undefined,
        work_types: filters.work_types?.length ? filters.work_types : undefined,
        languages: filters.languages?.length ? filters.languages : undefined,
        genres: filters.genres?.length ? filters.genres : undefined,
        public_domain: filters.public_domain?.length
            ? filters.public_domain
            : undefined,
        controlled: filters.controlled?.length ? filters.controlled : undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        writer_id: filters.writer_id ? String(filters.writer_id) : undefined,
        work_id: filters.work_id ? String(filters.work_id) : undefined,
        include_ended: filters.include_ended ? '1' : undefined,
        as_of: filters.as_of || undefined,
        change_types: filters.change_types?.length
            ? filters.change_types
            : undefined,
        users: filters.users?.length ? filters.users : undefined,
        effective_from: filters.effective_from || undefined,
        effective_to: filters.effective_to || undefined,
        recorded_from: filters.recorded_from || undefined,
        recorded_to: filters.recorded_to || undefined,
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

    if (params.types?.length) {
        query.types = params.types;
    }

    if (params.societies?.length) {
        query.societies = params.societies;
    }

    if (params.publishers?.length) {
        query.publishers = params.publishers;
    }

    if (params.rights_types?.length) {
        query.rights_types = params.rights_types;
    }

    if (params.territories?.length) {
        query.territories = params.territories;
    }

    if (params.registration_statuses?.length) {
        query.registration_statuses = params.registration_statuses;
    }

    if (params.conflict_statuses?.length) {
        query.conflict_statuses = params.conflict_statuses;
    }

    if (params.work_types?.length) {
        query.work_types = params.work_types;
    }

    if (params.languages?.length) {
        query.languages = params.languages;
    }

    if (params.genres?.length) {
        query.genres = params.genres;
    }

    if (params.public_domain?.length) {
        query.public_domain = params.public_domain;
    }

    if (params.controlled?.length) {
        query.controlled = params.controlled;
    }

    if (params.start_date) {
        query.start_date = params.start_date;
    }

    if (params.end_date) {
        query.end_date = params.end_date;
    }

    if (params.change_types?.length) {
        query.change_types = params.change_types;
    }

    if (params.users?.length) {
        query.users = params.users;
    }

    if (params.writer_id) {
        query.writer_id = params.writer_id;
    }

    if (params.work_id) {
        query.work_id = params.work_id;
    }

    if (params.include_ended) {
        query.include_ended = params.include_ended;
    }

    if (params.as_of) {
        query.as_of = params.as_of;
    }

    if (params.effective_from) {
        query.effective_from = params.effective_from;
    }

    if (params.effective_to) {
        query.effective_to = params.effective_to;
    }

    if (params.recorded_from) {
        query.recorded_from = params.recorded_from;
    }

    if (params.recorded_to) {
        query.recorded_to = params.recorded_to;
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
        'types',
        'societies',
        'publishers',
        'rights_types',
        'territories',
        'registration_statuses',
        'conflict_statuses',
        'work_types',
        'languages',
        'genres',
        'public_domain',
        'controlled',
        'start_date',
        'end_date',
        'writer_id',
        'work_id',
        'include_ended',
        'as_of',
        'effective_from',
        'effective_to',
        'recorded_from',
        'recorded_to',
        'change_types',
        'users',
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
