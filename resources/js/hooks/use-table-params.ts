import { router } from '@inertiajs/react';
import {
    useCallback,
    useMemo,
    useRef,
    useState,
    type Dispatch,
    type SetStateAction,
} from 'react';
import {
    DEFAULT_PARAMS,
    hasActiveFilters,
    paramsEqual,
    paramsToQuery,
} from '@/lib/table';
import type { Params } from '@/types';

type UseTableParamsOptions = {
    /** The URL of the index page that renders the table. */
    url: string;
    /** The params the server applied for the initial render. */
    initialParams: Params;
    /** The params that mean "no filters" for this table. */
    defaultParams?: Params;
    /** Props to refresh on each change, e.g. `['users', 'filters']`. */
    only?: string[];
};

/**
 * Datatable state that lives in the query string.
 *
 * It has the same shape as `useState<Params>`. Every change updates the UI
 * immediately and then performs an Inertia visit, so the server stays the
 * single source of truth for searching, filtering, sorting and pagination.
 */
export function useTableParams({
    url,
    initialParams,
    defaultParams = DEFAULT_PARAMS,
    only,
}: UseTableParamsOptions) {
    const [params, setParamsState] = useState<Params>(initialParams);
    const [isLoading, setIsLoading] = useState(false);
    const [hasError, setHasError] = useState(false);
    const paramsRef = useRef<Params>(initialParams);

    const visit = useCallback(
        (next: Params) => {
            router.get(url, paramsToQuery(next, defaultParams), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
                onStart: () => {
                    setIsLoading(true);
                    setHasError(false);
                },
                onFinish: () => setIsLoading(false),
                onHttpException: () => {
                    setHasError(true);
                },
                onNetworkError: () => {
                    setHasError(true);
                },
            });
        },
        [url, defaultParams, only],
    );

    const setParams: Dispatch<SetStateAction<Params>> = useCallback(
        (action) => {
            const next =
                typeof action === 'function'
                    ? action(paramsRef.current)
                    : action;

            if (paramsEqual(next, paramsRef.current)) {
                return;
            }

            paramsRef.current = next;
            setParamsState(next);
            visit(next);
        },
        [visit],
    );

    const isFiltered = useMemo(
        () => hasActiveFilters(params, defaultParams),
        [params, defaultParams],
    );

    const resetFilters = useCallback(() => {
        setParams((previous) => ({
            ...defaultParams,
            page: 1,
            per_page: previous.per_page,
            sort: previous.sort,
        }));
    }, [defaultParams, setParams]);

    const retry = useCallback(() => visit(paramsRef.current), [visit]);

    return {
        params,
        setParams,
        isFiltered,
        isLoading,
        hasError,
        resetFilters,
        retry,
    };
}
