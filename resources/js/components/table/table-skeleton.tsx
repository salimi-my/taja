import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

interface TableSkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
    /**
     * The number of columns in the table.
     * @type number
     */
    columnCount: number;

    /**
     * The number of rows in the table.
     * @default 10
     * @type number | undefined
     */
    rowCount?: number;

    /**
     * The number of searchable columns in the table.
     * @default 0
     * @type number | undefined
     */
    searchableColumnCount?: number;

    /**
     * The number of filterable columns in the table.
     * @default 0
     * @type number | undefined
     */
    filterableColumnCount?: number;

    /**
     * The number of right buttons in the table.
     * @default 0
     * @type number | undefined
     */
    rightButtonCount?: number;

    /**
     * The width of each cell in the table.
     * The length of the array should be equal to the columnCount.
     * Any valid CSS width value is accepted.
     * @default ["auto"]
     * @type string[] | undefined
     */
    cellWidths?: string[];

    /**
     * Flag to show the pagination bar.
     * @default true
     * @type boolean | undefined
     */
    withPagination?: boolean;

    /**
     * Flag to prevent the table cells from shrinking.
     * @default false
     * @type boolean | undefined
     */
    shrinkZero?: boolean;

    /**
     * Flag to enable mobile card layout skeleton.
     * When true, renders card layout on mobile and table on desktop.
     * @default false
     * @type boolean | undefined
     */
    mobileCard?: boolean;
}

export function TableSkeleton(props: TableSkeletonProps) {
    const {
        columnCount,
        rowCount = 10,
        searchableColumnCount = 0,
        filterableColumnCount = 0,
        rightButtonCount = 0,
        cellWidths = ['auto'],
        withPagination = true,
        shrinkZero = false,
        mobileCard = false,
        className,
        ...skeletonProps
    } = props;

    // Mobile Card Skeleton (visible only on mobile when mobileCard is true)
    const mobileCardSkeleton = mobileCard ? (
        <div className="block w-full space-y-4 md:hidden">
            {/* Mobile Toolbar */}
            {(searchableColumnCount > 0 ||
                filterableColumnCount > 0 ||
                rightButtonCount > 0) && (
                <div className="space-y-3">
                    <div className="flex items-center gap-2">
                        {/* Searchbar skeleton */}
                        <Skeleton className="h-8 flex-1" />
                        {/* Filter button skeleton */}
                        <Skeleton className="h-8 w-8 flex-shrink-0" />
                    </div>
                </div>
            )}

            {/* Mobile Card Skeletons */}
            <div className="space-y-3">
                {Array.from({ length: rowCount }).map((_, i) => (
                    <Skeleton key={i} className="h-35 w-full rounded-lg" />
                ))}
            </div>

            {/* Pagination for mobile */}
            {withPagination ? (
                <div className="flex w-full flex-col-reverse items-center justify-between gap-4 overflow-auto py-1 sm:flex-row sm:gap-8">
                    <Skeleton className="h-7 w-48 shrink-0" />
                    <div className="flex flex-col-reverse items-center gap-4 sm:flex-row sm:gap-6 lg:gap-8">
                        <div className="flex items-center space-x-2">
                            <Skeleton className="h-7 w-24" />
                            <Skeleton className="h-7 w-[4.5rem]" />
                        </div>
                        <div className="flex items-center space-x-2">
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    ) : null;

    // Desktop Table Skeleton
    const desktopTableSkeleton = (
        <div
            className={cn(
                'w-full space-y-4 overflow-auto',
                mobileCard ? 'hidden md:block' : '',
                className,
            )}
            {...skeletonProps}
        >
            {/* Desktop Toolbar */}
            {(searchableColumnCount > 0 ||
                filterableColumnCount > 0 ||
                rightButtonCount > 0) && (
                <div className="flex w-full items-center justify-between space-x-2 overflow-auto py-1">
                    <div className="flex flex-1 items-center space-x-2">
                        {searchableColumnCount > 0
                            ? Array.from({ length: searchableColumnCount }).map(
                                  (_, i) => (
                                      <Skeleton
                                          key={i}
                                          className="h-7 w-40 lg:w-60"
                                      />
                                  ),
                              )
                            : null}
                        {filterableColumnCount > 0
                            ? Array.from({ length: filterableColumnCount }).map(
                                  (_, i) => (
                                      <Skeleton
                                          key={i}
                                          className="h-7 w-[4.5rem] border-dashed"
                                      />
                                  ),
                              )
                            : null}
                    </div>
                    {rightButtonCount > 0
                        ? Array.from({ length: rightButtonCount }).map(
                              (_, i) => (
                                  <Skeleton
                                      key={i}
                                      className="flex h-7 w-[6rem]"
                                  />
                              ),
                          )
                        : null}
                </div>
            )}

            {/* Desktop Table */}
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {Array.from({ length: 1 }).map((_, i) => (
                            <TableRow key={i} className="hover:bg-transparent">
                                {Array.from({ length: columnCount }).map(
                                    (_, j) => (
                                        <TableHead
                                            key={j}
                                            style={{
                                                width: cellWidths[j],
                                                minWidth: shrinkZero
                                                    ? cellWidths[j]
                                                    : 'auto',
                                            }}
                                        >
                                            <Skeleton className="h-6 w-full" />
                                        </TableHead>
                                    ),
                                )}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {Array.from({ length: rowCount }).map((_, i) => (
                            <TableRow key={i} className="hover:bg-transparent">
                                {Array.from({ length: columnCount }).map(
                                    (_, j) => (
                                        <TableCell
                                            key={j}
                                            style={{
                                                width: cellWidths[j],
                                                minWidth: shrinkZero
                                                    ? cellWidths[j]
                                                    : 'auto',
                                            }}
                                        >
                                            <Skeleton className="h-6 w-full" />
                                        </TableCell>
                                    ),
                                )}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Pagination for desktop */}
            {withPagination ? (
                <div className="flex w-full flex-col-reverse items-center justify-between gap-4 overflow-auto py-1 sm:flex-row sm:gap-8">
                    <Skeleton className="h-7 w-48 shrink-0" />
                    <div className="flex flex-col-reverse items-center gap-4 sm:flex-row sm:gap-6 lg:gap-8">
                        <div className="flex items-center space-x-2">
                            <Skeleton className="h-7 w-24" />
                            <Skeleton className="h-7 w-[4.5rem]" />
                        </div>
                        <div className="flex items-center space-x-2">
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                            <Skeleton className="size-7" />
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );

    return (
        <>
            {mobileCardSkeleton}
            {desktopTableSkeleton}
        </>
    );
}
