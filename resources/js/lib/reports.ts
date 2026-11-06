import { DEFAULT_PARAMS, paramsToQuery } from '@/lib/table';
import type { Params } from '@/types';

/** The default params of a report table, with the sort the server applies. */
export function reportDefaults(sort: string): Params {
    return { ...DEFAULT_PARAMS, sort };
}

/**
 * The query of a report's CSV export: the filters of the screen without the
 * page number, because the export always contains every row.
 */
export function exportQuery(
    params: Params,
    defaultParams: Params,
): Record<string, string | number | string[]> {
    const query = paramsToQuery(params, defaultParams);

    delete query.page;
    delete query.per_page;

    return query;
}

/** Share totals other than 100 mean the split is incomplete. */
export function isCompleteTotal(total: number | null): boolean {
    return total === null || Math.abs(total - 100) < 0.005;
}

/** Format a total such as `80` as `80%`, or a dash when it is empty. */
export function formatTotal(total: number | null): string {
    return total === null ? '-' : `${total}%`;
}
