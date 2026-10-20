import { ChevronDown, ChevronUp, ListFilter, Loader2 } from 'lucide-react';
import { useState, type Dispatch, type SetStateAction } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { handleInfiniteScroll } from '@/lib/infinite-scroll';
import { capitalizeWords, cn } from '@/lib/utils';
import type { ArrayFilterParamKey, FilterOption, Params } from '@/types';

/** Configuration for a single filter section inside `FilterDrawer`. */
export interface FilterGroup {
    /** Unique key used to track this group's temporary selection state. */
    key: string;
    /** Section heading displayed above the options list. */
    label: string;
    /** Selectable options rendered as checkboxes (multiple) or radio buttons (single). */
    options: FilterOption[];
    /** The `Params` key updated when the user applies this filter (e.g. `"roles"`). */
    paramKey: ArrayFilterParamKey;
    /** Allow selecting multiple options simultaneously. Defaults to `true`. */
    multiple?: boolean;
    /** `true` when more pages are available for infinite scroll. */
    hasMore?: boolean;
    /** `true` while the next page is being fetched. Renders a "Loading more…" indicator. */
    isLoadingMore?: boolean;
    /** Called when the user scrolls to the bottom of the list. Pair with `hasMore` and `isLoadingMore`. */
    onLoadMore?: () => void;
    /**
     * Called with the current search input value.
     * When provided, a search input is rendered above the options list.
     * Pair with `useInfinitePaginatedOptions`'s `setSearch`.
     */
    onSearch?: (value: string) => void;
    /** Controlled search input value. Pair with `useInfinitePaginatedOptions`'s `search`. */
    searchValue?: string;
    /**
     * `true` when a server-side search request is in flight.
     * Shows a spinner inside the search input and an empty "Searching…" state.
     * Only has visible effect when `onSearch` is also provided.
     */
    isFetching?: boolean;
    /** Optional leading content rendered before each option label. */
    renderOptionLeading?: (option: FilterOption) => React.ReactNode;
}

/** Configuration for a sortable column in `FilterDrawer`. */
export interface SortColumn {
    /** Value appended to the sort param (e.g. `"name"` → `"name.asc"`). */
    value: string;
    /** Human-readable column label. */
    label: string;
    /** Icon rendered next to the label. */
    icon: React.ComponentType<{ className?: string }>;
}

interface FilterDrawerProps {
    /** Current table query params. */
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    /** Filter sections to display. Each group maps to one `Params` key. */
    filterGroups: FilterGroup[];
    /** Columns available for sorting, rendered as asc/desc toggle pairs. */
    sortColumns: SortColumn[];
    /** Drawer heading. Defaults to `"Filters & Sort"`. */
    title?: string;
    /** Drawer sub-heading. Defaults to `"Filter and sort the list"`. */
    description?: string;
    /** Tailwind max-height class for the sort columns list. Defaults to `"max-h-[200px]"`. */
    sortMaxHeight?: string;
    /** Called when the drawer open state changes. */
    onOpenChange?: (open: boolean) => void;
    /** The sort that applies when the user has not chosen one, e.g. `created_at.desc`. */
    defaultSort?: string;
}

/**
 * A mobile-friendly drawer for filtering and sorting a table.
 *
 * Renders a funnel icon button that opens a bottom drawer containing one or more
 * `FilterGroup` sections and a set of sortable columns. Selections are held in
 * temporary state and only applied when the user taps "Apply", making it safe
 * to browse options without immediately affecting the table.
 *
 * Each `FilterGroup` supports optional server-side search — provide `onSearch`
 * and `isFetching` (from `useInfinitePaginatedOptions`) to render a search input
 * with a loading spinner above the options list.
 *
 * @param props.params - Current table query params.
 * @param props.setParams - State setter for the table query params.
 * @param props.filterGroups - Filter sections to display. Each group maps to one `Params` key.
 * @param props.sortColumns - Columns available for sorting, rendered as asc/desc toggle pairs.
 * @param props.title - Drawer heading. Defaults to `"Filters & Sort"`.
 * @param props.description - Drawer sub-heading. Defaults to `"Filter and sort the list"`.
 * @param props.sortMaxHeight - Tailwind max-height class for the sort columns list. Defaults to `"max-h-[200px]"`.
 */
export function FilterDrawer({
    params,
    setParams,
    filterGroups,
    sortColumns,
    title = 'Filters & Sort',
    description = 'Filter and sort the list',
    sortMaxHeight = 'max-h-[200px]',
    onOpenChange,
    defaultSort,
}: FilterDrawerProps) {
    const renderOptionLabel = (group: FilterGroup, option: FilterOption) => (
        <>
            {group.renderOptionLeading?.(option)}
            {capitalizeWords(option.name)}
        </>
    );

    const [open, setOpen] = useState(false);

    // Initialize temporary state for all filter groups
    const initTempFilters = () => {
        const filters: Record<string, string[]> = {};
        filterGroups.forEach((group) => {
            const paramValue = params[group.paramKey];
            filters[group.key] = Array.isArray(paramValue) ? paramValue : [];
        });
        return filters;
    };

    const [tempFilters, setTempFilters] =
        useState<Record<string, string[]>>(initTempFilters());
    const [tempSort, setTempSort] = useState<string | undefined>(params.sort);

    const handleFilterToggle = (
        groupKey: string,
        filterId: string,
        multiple: boolean = true,
    ) => {
        setTempFilters((prev) => {
            if (multiple) {
                // Multiple selection mode (existing behavior)
                return {
                    ...prev,
                    [groupKey]: prev[groupKey].includes(filterId)
                        ? prev[groupKey].filter((id) => id !== filterId)
                        : [...prev[groupKey], filterId],
                };
            } else {
                // Single selection mode
                const currentValue = prev[groupKey];
                const newValue =
                    currentValue.length === 1 && currentValue[0] === filterId
                        ? []
                        : [filterId];
                return {
                    ...prev,
                    [groupKey]: newValue,
                };
            }
        });
    };

    const handleApply = () => {
        const updates: Partial<Params> = { page: 1 };

        // Apply filter groups
        filterGroups.forEach((group) => {
            const filterValue = tempFilters[group.key];
            (updates as Record<string, string[] | undefined>)[group.paramKey] =
                filterValue.length > 0 ? filterValue : undefined;
        });

        // Apply sort
        updates.sort = tempSort;

        setParams((prev) => ({
            ...prev,
            ...updates,
        }));
        setOpen(false);
    };

    const handleReset = () => {
        const resetFilters: Record<string, string[]> = {};
        filterGroups.forEach((group) => {
            resetFilters[group.key] = [];
        });
        setTempFilters(resetFilters);
        setTempSort(defaultSort);

        const updates: Partial<Params> = { page: 1 };
        filterGroups.forEach((group) => {
            (updates as Record<string, string[] | undefined>)[group.paramKey] =
                undefined;
        });
        updates.sort = defaultSort;

        setParams((prev) => ({
            ...prev,
            ...updates,
        }));
        setOpen(false);
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (newOpen) {
            // Reset temporary state to current params
            setTempFilters(initTempFilters());
            setTempSort(params.sort);

            // Remove focus from trigger button to prevent aria-hidden violation
            setTimeout(() => {
                if (document.activeElement instanceof HTMLElement) {
                    document.activeElement.blur();
                }
            }, 0);
        }
        setOpen(newOpen);
        onOpenChange?.(newOpen);
    };

    // Calculate active filters count
    const activeFiltersCount =
        filterGroups.reduce((count, group) => {
            const paramValue = params[group.paramKey];
            return count + (Array.isArray(paramValue) ? paramValue.length : 0);
        }, 0) + (params.sort && params.sort !== defaultSort ? 1 : 0);

    // Check if any temp filters are selected
    const hasTempFilters = Object.values(tempFilters).some(
        (filters) => filters.length > 0,
    );

    return (
        <Drawer
            repositionInputs={false}
            open={open}
            onOpenChange={handleOpenChange}
            modal={true}
        >
            <DrawerTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className="relative h-8 w-8 flex-shrink-0"
                    aria-label="Open filters"
                    onClick={(e) => {
                        // Blur the button immediately to prevent aria-hidden focus violation
                        e.currentTarget.blur();
                    }}
                >
                    <ListFilter className="size-4" />
                    {activeFiltersCount > 0 && (
                        <span className="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground">
                            {activeFiltersCount > 9 ? '9+' : activeFiltersCount}
                        </span>
                    )}
                </Button>
            </DrawerTrigger>
            <DrawerContent className="overflow-hidden bg-card">
                <DrawerHeader>
                    <DrawerTitle>{title}</DrawerTitle>
                    <DrawerDescription>{description}</DrawerDescription>
                </DrawerHeader>

                <div className="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-4">
                    {filterGroups.length > 0 && (
                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold">Filters</h3>

                            {filterGroups.map((group) => {
                                const isMultiple = group.multiple !== false; // defaults to true
                                return (
                                    <div
                                        key={group.key}
                                        className="rounded-lg border p-3"
                                    >
                                        <h3 className="pb-2.5 text-sm font-medium">
                                            {group.label}
                                        </h3>
                                        {isMultiple ? (
                                            <div className="space-y-2">
                                                {group.onSearch && (
                                                    <div className="relative">
                                                        <Input
                                                            placeholder={`Search ${group.label.toLowerCase()}…`}
                                                            className="h-7 pr-7 text-xs"
                                                            value={
                                                                group.searchValue ??
                                                                ''
                                                            }
                                                            onChange={(e) =>
                                                                group.onSearch!(
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        {group.isFetching && (
                                                            <Loader2 className="absolute top-1/2 right-2 size-3 -translate-y-1/2 animate-spin text-muted-foreground" />
                                                        )}
                                                    </div>
                                                )}
                                                <div
                                                    onScroll={(event) => {
                                                        if (!group.onLoadMore) {
                                                            return;
                                                        }
                                                        handleInfiniteScroll({
                                                            event,
                                                            hasMore: Boolean(
                                                                group.hasMore,
                                                            ),
                                                            isLoadingMore:
                                                                Boolean(
                                                                    group.isLoadingMore,
                                                                ),
                                                            onLoadMore:
                                                                group.onLoadMore,
                                                        });
                                                    }}
                                                    className="flex max-h-[80px] flex-col gap-2 overflow-y-auto overscroll-contain"
                                                >
                                                    {group.options.length ===
                                                        0 &&
                                                    group.isFetching ? (
                                                        <p className="flex items-center justify-center gap-1.5 py-1 text-xs text-muted-foreground">
                                                            <Loader2 className="size-3 animate-spin" />
                                                            Searching…
                                                        </p>
                                                    ) : group.options.length ===
                                                      0 ? (
                                                        <p className="py-1 text-center text-xs text-muted-foreground">
                                                            No results found.
                                                        </p>
                                                    ) : (
                                                        group.options.map(
                                                            (option) => (
                                                                <div
                                                                    key={
                                                                        option.id
                                                                    }
                                                                    className="flex shrink-0 items-center space-x-1.5 overflow-hidden"
                                                                >
                                                                    <Checkbox
                                                                        id={`${group.key}-${option.id}`}
                                                                        checked={tempFilters[
                                                                            group
                                                                                .key
                                                                        ]?.includes(
                                                                            String(
                                                                                option.id,
                                                                            ),
                                                                        )}
                                                                        onCheckedChange={() =>
                                                                            handleFilterToggle(
                                                                                group.key,
                                                                                String(
                                                                                    option.id,
                                                                                ),
                                                                                true,
                                                                            )
                                                                        }
                                                                    />
                                                                    <Label
                                                                        htmlFor={`${group.key}-${option.id}`}
                                                                        className="flex flex-1 cursor-pointer items-center gap-1.5 text-xs font-medium"
                                                                    >
                                                                        {renderOptionLabel(
                                                                            group,
                                                                            option,
                                                                        )}
                                                                    </Label>
                                                                </div>
                                                            ),
                                                        )
                                                    )}
                                                    {group.isLoadingMore && (
                                                        <p className="text-center text-xs text-muted-foreground">
                                                            Loading more...
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <div
                                                onScroll={(event) => {
                                                    if (!group.onLoadMore) {
                                                        return;
                                                    }
                                                    handleInfiniteScroll({
                                                        event,
                                                        hasMore: Boolean(
                                                            group.hasMore,
                                                        ),
                                                        isLoadingMore: Boolean(
                                                            group.isLoadingMore,
                                                        ),
                                                        onLoadMore:
                                                            group.onLoadMore,
                                                    });
                                                }}
                                                className="max-h-[80px] overflow-y-auto overscroll-contain"
                                            >
                                                <RadioGroup
                                                    value={
                                                        tempFilters[
                                                            group.key
                                                        ]?.[0] || ''
                                                    }
                                                    onValueChange={(value) =>
                                                        handleFilterToggle(
                                                            group.key,
                                                            value,
                                                            false,
                                                        )
                                                    }
                                                    className="gap-2"
                                                >
                                                    {group.options.map(
                                                        (option) => (
                                                            <div
                                                                key={option.id}
                                                                className="flex shrink-0 items-center space-x-1.5 overflow-hidden"
                                                            >
                                                                <RadioGroupItem
                                                                    value={String(
                                                                        option.id,
                                                                    )}
                                                                    id={`${group.key}-${option.id}`}
                                                                />
                                                                <Label
                                                                    htmlFor={`${group.key}-${option.id}`}
                                                                    className="flex flex-1 cursor-pointer items-center gap-1.5 text-xs font-medium"
                                                                >
                                                                    {renderOptionLabel(
                                                                        group,
                                                                        option,
                                                                    )}
                                                                </Label>
                                                            </div>
                                                        ),
                                                    )}
                                                    {group.isLoadingMore && (
                                                        <p className="text-center text-xs text-muted-foreground">
                                                            Loading more...
                                                        </p>
                                                    )}
                                                </RadioGroup>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {filterGroups.length > 0 && sortColumns.length > 0 && (
                        <Separator />
                    )}

                    {sortColumns.length > 0 && (
                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold">Sort By</h3>
                            <div
                                className={cn(
                                    'h-full space-y-1.5 overflow-y-auto',
                                    sortMaxHeight,
                                )}
                            >
                                {sortColumns.map((column) => {
                                    const currentSort = tempSort?.split('.')[0];
                                    const currentDirection = tempSort?.split(
                                        '.',
                                    )[1] as 'asc' | 'desc' | undefined;
                                    const isSelected =
                                        currentSort === column.value;
                                    const Icon = column.icon;

                                    return (
                                        <div
                                            key={column.value}
                                            className="flex items-center justify-between rounded-lg border p-1 ps-2.5 transition-colors"
                                        >
                                            <div className="flex items-center gap-1.5">
                                                <Icon className="size-3.5 flex-shrink-0 text-muted-foreground" />
                                                <span className="text-xs font-medium">
                                                    {column.label}
                                                </span>
                                            </div>
                                            <ToggleGroup
                                                type="single"
                                                value={
                                                    isSelected
                                                        ? currentDirection
                                                        : ''
                                                }
                                                onValueChange={(value) => {
                                                    if (value) {
                                                        setTempSort(
                                                            `${column.value}.${value}`,
                                                        );
                                                    } else {
                                                        // Clear sort when untoggling
                                                        setTempSort(undefined);
                                                    }
                                                }}
                                                spacing={1}
                                                variant="outline"
                                                size="sm"
                                            >
                                                <ToggleGroupItem
                                                    value="asc"
                                                    size="sm"
                                                    aria-label="Sort ascending"
                                                    className="w-8 data-[state=on]:bg-primary data-[state=on]:text-primary-foreground"
                                                >
                                                    <ChevronUp />
                                                </ToggleGroupItem>
                                                <ToggleGroupItem
                                                    value="desc"
                                                    size="sm"
                                                    aria-label="Sort descending"
                                                    className="w-8 data-[state=on]:bg-primary data-[state=on]:text-primary-foreground"
                                                >
                                                    <ChevronDown />
                                                </ToggleGroupItem>
                                            </ToggleGroup>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>

                <DrawerFooter className="flex-row gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={handleReset}
                        className="flex-1"
                        disabled={
                            activeFiltersCount === 0 &&
                            !hasTempFilters &&
                            (!tempSort || tempSort === defaultSort)
                        }
                    >
                        Reset
                    </Button>
                    <DrawerClose asChild>
                        <Button
                            size="sm"
                            onClick={handleApply}
                            className="flex-1"
                        >
                            Apply
                        </Button>
                    </DrawerClose>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}
