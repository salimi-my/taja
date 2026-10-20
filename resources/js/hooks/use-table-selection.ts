import {
    useCallback,
    useEffect,
    useMemo,
    useState,
    type Dispatch,
    type SetStateAction,
} from 'react';

import type { Params } from '@/types';

/** Tri-state value for a "select all" checkbox (Radix-compatible). */
export function getSelectAllCheckedState(
    selectedCount: number,
    totalSelectable: number,
): boolean | 'indeterminate' {
    if (totalSelectable === 0) return false;
    if (selectedCount === totalSelectable) return true;
    if (selectedCount > 0) return 'indeterminate';
    return false;
}

/** Clears row selection whenever table query params change (page, filters, search, sort). */
export function useClearSelectionOnParamsChange<T>(
    params: Params,
    setSelectedIds: Dispatch<SetStateAction<Set<T>>>,
) {
    useEffect(() => {
        setSelectedIds(new Set());
    }, [params, setSelectedIds]);
}

interface UseTableSelectionOptions<TId> {
    params: Params;
    /** IDs of rows on the current page (used for select-all). */
    pageIds: TId[];
    /** When provided, rows failing this check are excluded from selection and select-all. */
    isSelectable?: (id: TId) => boolean;
}

export function useTableSelection<TId>({
    params,
    pageIds,
    isSelectable = () => true,
}: UseTableSelectionOptions<TId>) {
    const [selectedIds, setSelectedIds] = useState<Set<TId>>(new Set());

    useClearSelectionOnParamsChange(params, setSelectedIds);

    const selectableIds = useMemo(
        () => pageIds.filter(isSelectable),
        [pageIds, isSelectable],
    );

    const selectableCount = selectableIds.length;

    const selectAllChecked = useMemo(
        () => getSelectAllCheckedState(selectedIds.size, selectableCount),
        [selectedIds, selectableCount],
    );

    const handleToggleSelectAll = useCallback(() => {
        if (selectableIds.length === 0) return;

        const allIds = new Set(selectableIds);
        setSelectedIds((prev) =>
            prev.size === allIds.size ? new Set() : allIds,
        );
    }, [selectableIds]);

    const handleToggleSelect = useCallback(
        (id: TId) => {
            if (!isSelectable(id)) return;

            setSelectedIds((prev) => {
                const next = new Set(prev);
                if (next.has(id)) {
                    next.delete(id);
                } else {
                    next.add(id);
                }
                return next;
            });
        },
        [isSelectable],
    );

    return {
        selectedIds,
        setSelectedIds,
        handleToggleSelect,
        handleToggleSelectAll,
        selectAllChecked,
        selectableCount,
    };
}
