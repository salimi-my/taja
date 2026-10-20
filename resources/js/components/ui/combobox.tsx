/*
 * CUSTOMIZATIONS MADE TO COMBOBOX COMPONENT:
 *
 * 1. Check icon placed on the right side via the built-in CommandItem CheckIcon
 *    (activated by passing data-checked="true" instead of rendering a manual icon)
 * 2. Selected label and option labels are truncated when the container is too narrow
 * 3. Infinite scroll / load-more support via handleInfiniteScroll on the CommandGroup onScroll
 * 4. Loading state rendered as a disabled CommandItem with a Spinner icon
 * 5. Server-side search: pass onSearch to disable cmdk client-side filtering and send
 *    debounced queries to the API; pair with searchValue (from useInfinitePaginatedOptions.search)
 *    and isFetching to show a spinner in the input.
 * 6. Optional leading content rendered before each option label (and in the trigger when selected).
 *
 * Supports two data modes:
 *  - Static: pass a plain `options` array; cmdk handles client-side filtering automatically.
 *  - Dynamic (API): pass `onSearch` + `searchValue` + `isFetching` (from useInfinitePaginatedOptions) and
 *    `onLoadMore` + `hasMore` + `isLoadingMore` for paginated infinite-scroll fetching.
 *
 * This is a custom component built on top of shadcn/ui's Command and Popover primitives.
 */


import { ChevronsUpDown, Loader2 } from "lucide-react";
import * as React from "react";

import { Button } from "@/components/ui/button";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Spinner } from "@/components/ui/spinner";
import { handleInfiniteScroll } from "@/lib/infinite-scroll";
import { cn } from "@/lib/utils";

export interface ComboboxOption {
  value: string | number;
  label: string;
}

export interface ComboboxProps {
  options: ComboboxOption[];
  value?: string | number | null;
  onValueChange?: (value: string | number) => void;
  "aria-invalid"?: boolean;
  placeholder?: string;
  searchPlaceholder?: string;
  emptyText?: string;
  disabled?: boolean;
  className?: string;
  /** `true` when more pages are available for infinite scroll. */
  hasMore?: boolean;
  /** `true` while the next page is being fetched (load-more). */
  isLoadingMore?: boolean;
  /** Called when the user scrolls to the bottom of the list. */
  onLoadMore?: () => void;
  /**
   * Called with the current search input value for server-side search.
   * When provided, cmdk's built-in client-side filtering is disabled.
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
  /** Optional leading content rendered before each option label (and in the trigger when selected). */
  renderOptionLeading?: (option: ComboboxOption) => React.ReactNode;
}

function Combobox({
  options,
  value,
  onValueChange,
  "aria-invalid": ariaInvalid,
  placeholder = "Select option...",
  searchPlaceholder = "Search...",
  emptyText = "No option found.",
  disabled,
  className,
  hasMore,
  isLoadingMore,
  onLoadMore,
  onSearch,
  searchValue,
  isFetching = false,
  renderOptionLeading,
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false);

  const selectedOption = options.find((opt) => opt.value === value);

  return (
    <Popover open={open} onOpenChange={setOpen} modal={true}>
      <PopoverTrigger asChild>
        <Button
          data-slot="combobox-trigger"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          aria-invalid={ariaInvalid}
          disabled={disabled}
          className={cn(
            "w-full justify-between font-normal",
            !selectedOption && "text-muted-foreground",
            className,
          )}
        >
          <span className="flex min-w-0 flex-1 items-center gap-2 truncate">
            {selectedOption && renderOptionLeading ? (
              <>
                {renderOptionLeading(selectedOption)}
                <span className="truncate">{selectedOption.label}</span>
              </>
            ) : (
              <span className="truncate">
                {selectedOption ? selectedOption.label : placeholder}
              </span>
            )}
          </span>
          <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent
        data-slot="combobox-content"
        className="w-(--radix-popover-trigger-width) p-0"
        align="start"
      >
        <Command shouldFilter={!onSearch}>
          <div className="relative">
            <CommandInput
              placeholder={searchPlaceholder}
              value={searchValue ?? ""}
              onValueChange={onSearch}
            />
            {isFetching && onSearch && (
              <Loader2 className="text-muted-foreground absolute top-1/2 right-3 size-3.5 -translate-y-1/2 animate-spin" />
            )}
          </div>
          <CommandList>
            <CommandEmpty>
              {isFetching && onSearch ? (
                <span className="flex items-center justify-center gap-1.5">
                  <Loader2 className="size-3 animate-spin" />
                  Searching…
                </span>
              ) : (
                emptyText
              )}
            </CommandEmpty>
            <CommandGroup
              className="max-h-[250px] overflow-auto"
              onScroll={
                onLoadMore
                  ? (event) =>
                      handleInfiniteScroll({
                        event,
                        hasMore: hasMore ?? false,
                        isLoadingMore: isLoadingMore ?? false,
                        onLoadMore,
                      })
                  : undefined
              }
            >
              {options.map((option) => (
                <CommandItem
                  key={option.value}
                  value={String(option.value)}
                  data-checked={option.value === value ? "true" : undefined}
                  onSelect={() => {
                    onValueChange?.(option.value);
                    setOpen(false);
                  }}
                >
                  {renderOptionLeading ? (
                    <span className="flex min-w-0 flex-1 items-center gap-2">
                      {renderOptionLeading(option)}
                      <span className="truncate">{option.label}</span>
                    </span>
                  ) : (
                    <span className="truncate">{option.label}</span>
                  )}
                </CommandItem>
              ))}
              {isLoadingMore && (
                <CommandItem
                  disabled
                  className="justify-center gap-1.5 text-xs [&>svg:last-child]:hidden"
                >
                  <Spinner className="size-3" />
                  Loading more...
                </CommandItem>
              )}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}

export { Combobox };
