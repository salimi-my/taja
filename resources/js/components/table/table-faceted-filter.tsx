import { Check, Loader2, PlusCircle } from 'lucide-react';
import { useMemo, type Dispatch, type SetStateAction } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { handleInfiniteScroll } from '@/lib/infinite-scroll';
import { capitalizeWords, cn } from '@/lib/utils';
import type { ArrayFilterParamKey, FilterOption, Params } from '@/types';

interface TableFacetedFilterProps {
    /** Label shown on the trigger button and used as the search input placeholder. */
    title: string;
    /** The `Params` key this filter controls (e.g. `"roles"`). */
    column: ArrayFilterParamKey;
    /** Current table query params. */
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    /** List of selectable options. */
    options: FilterOption[];
    /**
     * Options accumulated across searches/pages for resolving selected badge labels.
     * Pair with `useInfinitePaginatedOptions`'s `knownOptions`.
     */
    knownOptions?: FilterOption[];
    /** Allow selecting multiple options simultaneously. Defaults to `true`. */
    multiple?: boolean;
    /** `true` when more pages are available for infinite scroll. */
    hasMore?: boolean;
    /** `true` while the next page is being fetched. Renders a "Loading more…" indicator at the bottom of the list. */
    isLoadingMore?: boolean;
    /** Called when the user scrolls to the bottom of the list. Pair with `hasMore` and `isLoadingMore`. */
    onLoadMore?: () => void;
    /**
     * Called with the current search input value.
     * When provided, cmdk's built-in client-side filtering is disabled and a spinner
     * is shown in the input while `isFetching` is `true`.
     * Pair with `useInfinitePaginatedOptions`'s `setSearch`.
     */
    onSearch?: (value: string) => void;
    /** Controlled search input value. Pair with `useInfinitePaginatedOptions`'s `search`. */
    searchValue?: string;
    /**
     * `true` when a server-side search request is in flight.
     * Shows a spinner inside the input and replaces the empty state with "Searching…".
     * Only has visible effect when `onSearch` is also provided.
     */
    isFetching?: boolean;
    /** Optional leading content rendered before each option label. */
    renderOptionLeading?: (option: FilterOption) => React.ReactNode;
    /** Extra classes merged onto the popover panel. Overrides default width when conflicting. */
    contentClassName?: string;
}

/**
 * A popover filter button for table toolbars.
 *
 * Renders a dashed trigger button that opens a searchable, scrollable list of options.
 * Supports single or multiple selection, infinite scroll, and optional server-side search.
 *
 * For server-side search, pair with `useInfinitePaginatedOptions`:
 * - Pass `query.setSearch` → `onSearch` to send debounced search requests to the API.
 * - Pass `query.search` → `searchValue` so the input stays in sync when the popover reopens.
 * - Pass `query.isFetching` → `isFetching` to show a spinner while results load.
 * - Pass `query.loadMore` → `onLoadMore` and `query.hasMore` → `hasMore` for infinite scroll.
 *
 * When `onSearch` is omitted, the built-in cmdk client-side filtering is used instead.
 *
 * @param props.title - Label shown on the trigger button and used as the search input placeholder.
 * @param props.column - The `Params` key this filter controls (e.g. `"roles"`).
 * @param props.params - Current table query params.
 * @param props.setParams - State setter for the table query params.
 * @param props.options - List of selectable options.
 * @param props.multiple - Allow selecting multiple options simultaneously. Defaults to `true`.
 * @param props.hasMore - `true` when more pages are available for infinite scroll.
 * @param props.isLoadingMore - `true` while the next page is being fetched.
 * @param props.onLoadMore - Called when the user scrolls to the bottom of the list.
 * @param props.onSearch - Called with the search input value. Disables cmdk client-side filtering when provided.
 * @param props.isFetching - `true` when a server-side request is in flight. Shows a spinner in the input.
 */
export function TableFacetedFilter({
    title,
    column,
    params,
    setParams,
    options,
    knownOptions,
    multiple = true,
    hasMore = false,
    isLoadingMore = false,
    onLoadMore,
    onSearch,
    searchValue,
    isFetching = false,
    renderOptionLeading,
    contentClassName,
}: TableFacetedFilterProps) {
    const selectedItems = useMemo(() => params[column] ?? [], [params, column]);
    const hasSelected = selectedItems.length > 0;

    const selectedOptionsForDisplay = useMemo(() => {
        const lookup = new Map<string, FilterOption>();

        knownOptions?.forEach((option) =>
            lookup.set(String(option.id), option),
        );
        options.forEach((option) => lookup.set(String(option.id), option));

        return selectedItems
            .map((id) => lookup.get(id))
            .filter((option) => option !== undefined);
    }, [knownOptions, options, selectedItems]);

    const toggleSelection = (id: number | string) => {
        setParams((prevParams: Params) => {
            if (multiple) {
                // Multiple selection mode (existing behavior)
                const newValues = selectedItems.includes(String(id))
                    ? selectedItems.filter((value) => value !== String(id))
                    : [...selectedItems, String(id)];

                return {
                    ...prevParams,
                    page: 1,
                    [column]: newValues.length ? newValues : undefined,
                };
            } else {
                // Single selection mode
                const newValue =
                    selectedItems.length === 1 &&
                    selectedItems[0] === String(id)
                        ? undefined
                        : [String(id)];

                return {
                    ...prevParams,
                    page: 1,
                    [column]: newValue,
                };
            }
        });
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    aria-label={`Filter ${title}`}
                    variant="outline"
                    size="sm"
                    className="h-8 border-dashed"
                >
                    <PlusCircle />
                    {title}
                    {hasSelected && (
                        <>
                            <Separator
                                orientation="vertical"
                                className="mx-2 h-5 self-center!"
                            />
                            {multiple ? (
                                <>
                                    <Badge
                                        variant="secondary"
                                        className="rounded-sm px-1 font-normal lg:hidden"
                                    >
                                        {selectedItems.length}
                                    </Badge>
                                    <div className="hidden space-x-1 lg:flex">
                                        {selectedItems.length > 2 ? (
                                            <Badge
                                                variant="secondary"
                                                className="rounded-sm px-1 font-normal"
                                            >
                                                {selectedItems.length} selected
                                            </Badge>
                                        ) : (
                                            selectedOptionsForDisplay.map(
                                                (option) => (
                                                    <Badge
                                                        key={option.id}
                                                        variant="secondary"
                                                        className="rounded-sm px-1 font-normal"
                                                    >
                                                        {capitalizeWords(
                                                            option.name,
                                                        )}
                                                    </Badge>
                                                ),
                                            )
                                        )}
                                    </div>
                                </>
                            ) : (
                                <Badge
                                    variant="secondary"
                                    className="rounded-sm px-1 font-normal"
                                >
                                    {selectedOptionsForDisplay[0]
                                        ? capitalizeWords(
                                              selectedOptionsForDisplay[0].name,
                                          )
                                        : ''}
                                </Badge>
                            )}
                        </>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className={cn('w-[250px] p-0', contentClassName)}
                align="start"
            >
                <Command shouldFilter={!onSearch}>
                    <div className="relative">
                        <CommandInput
                            placeholder={title}
                            value={searchValue ?? ''}
                            onValueChange={onSearch}
                        />
                        {isFetching && onSearch && (
                            <Loader2 className="absolute top-1/2 right-3 size-3.5 -translate-y-1/2 animate-spin text-muted-foreground" />
                        )}
                    </div>
                    <CommandList className="max-h-full">
                        <CommandEmpty>
                            {isFetching && onSearch ? (
                                <span className="flex items-center justify-center gap-1.5">
                                    <Loader2 className="size-3 animate-spin" />
                                    Searching…
                                </span>
                            ) : (
                                'No results found.'
                            )}
                        </CommandEmpty>
                        <CommandGroup
                            className="max-h-[250px] overflow-x-hidden overflow-y-auto"
                            onScroll={(event) => {
                                if (!onLoadMore) {
                                    return;
                                }
                                handleInfiniteScroll({
                                    event,
                                    hasMore,
                                    isLoadingMore,
                                    onLoadMore,
                                });
                            }}
                        >
                            {options.map((option) => {
                                const isSelected = selectedItems.includes(
                                    String(option.id),
                                );
                                return (
                                    <CommandItem
                                        key={option.id}
                                        onSelect={() =>
                                            toggleSelection(option.id)
                                        }
                                    >
                                        <div
                                            className={cn(
                                                'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                                                isSelected
                                                    ? 'bg-primary text-primary-foreground [&_svg]:text-primary-foreground!'
                                                    : 'opacity-50 [&_svg]:invisible',
                                            )}
                                        >
                                            <Check />
                                        </div>
                                        {renderOptionLeading ? (
                                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                                {renderOptionLeading(option)}
                                                <span className="truncate">
                                                    {capitalizeWords(
                                                        option.name,
                                                    )}
                                                </span>
                                            </span>
                                        ) : (
                                            <span className="truncate">
                                                {capitalizeWords(option.name)}
                                            </span>
                                        )}
                                    </CommandItem>
                                );
                            })}
                            {isLoadingMore && (
                                <p className="py-1.5 text-center text-xs text-muted-foreground">
                                    Loading more...
                                </p>
                            )}
                        </CommandGroup>
                        {hasSelected && (
                            <>
                                <CommandSeparator />
                                <CommandGroup>
                                    <CommandItem
                                        onSelect={() =>
                                            setParams((prevParams) => ({
                                                ...prevParams,
                                                page: 1,
                                                [column]: undefined,
                                            }))
                                        }
                                        className="justify-center text-center [&_svg]:hidden"
                                    >
                                        {multiple
                                            ? 'Clear filters'
                                            : 'Clear selection'}
                                    </CommandItem>
                                </CommandGroup>
                            </>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
