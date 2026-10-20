import type { Dispatch, ReactNode, SetStateAction } from 'react';

import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Params } from '@/types';

interface TablePaginationProps {
    total: number;
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
}

export function TablePagination({
    total,
    params,
    setParams,
}: TablePaginationProps) {
    const page = params.page ?? 1;
    const perPage = params.per_page ?? 10;
    const totalPageCount = Math.ceil((total || 0) / perPage);

    const renderPageNumbers = () => {
        const items: ReactNode[] = [];
        const maxVisiblePages = 5;

        if (totalPageCount <= maxVisiblePages) {
            for (let i = 1; i <= totalPageCount; i++) {
                items.push(
                    <PaginationItem key={i}>
                        <PaginationLink
                            isActive={page === i}
                            onClick={() =>
                                setParams((prevParams: Params) => ({
                                    ...prevParams,
                                    page: i,
                                    per_page: perPage,
                                }))
                            }
                        >
                            {i}
                        </PaginationLink>
                    </PaginationItem>,
                );
            }
        } else {
            items.push(
                <PaginationItem key={1}>
                    <PaginationLink
                        isActive={page === 1}
                        onClick={() =>
                            setParams((prevParams: Params) => ({
                                ...prevParams,
                                page: 1,
                                per_page: perPage,
                            }))
                        }
                    >
                        1
                    </PaginationLink>
                </PaginationItem>,
            );

            if (page > 3) {
                items.push(
                    <PaginationItem key="ellipsis-start">
                        <PaginationEllipsis />
                    </PaginationItem>,
                );
            }

            const start = Math.max(2, page - 3);
            const end = Math.min(totalPageCount - 1, page + 3);

            for (let i = start; i <= end; i++) {
                items.push(
                    <PaginationItem key={i}>
                        <PaginationLink
                            isActive={page === i}
                            onClick={() =>
                                setParams((prevParams: Params) => ({
                                    ...prevParams,
                                    page: i,
                                    per_page: perPage,
                                }))
                            }
                        >
                            {i}
                        </PaginationLink>
                    </PaginationItem>,
                );
            }

            if (page < totalPageCount - 2) {
                items.push(
                    <PaginationItem key="ellipsis-end">
                        <PaginationEllipsis />
                    </PaginationItem>,
                );
            }

            items.push(
                <PaginationItem key={totalPageCount}>
                    <PaginationLink
                        isActive={page === totalPageCount}
                        onClick={() =>
                            setParams((prevParams: Params) => ({
                                ...prevParams,
                                page: totalPageCount,
                                per_page: perPage,
                            }))
                        }
                    >
                        {totalPageCount}
                    </PaginationLink>
                </PaginationItem>,
            );
        }

        return items;
    };

    const handlePrevious = () => {
        setParams((prevParams: Params) => ({
            ...prevParams,
            page: (prevParams.page ?? 1) - 1,
        }));
    };

    const handleNext = () => {
        setParams((prevParams: Params) => ({
            ...prevParams,
            page: (prevParams.page ?? 1) + 1,
        }));
    };

    return (
        <div className="flex w-full flex-col-reverse items-center justify-between gap-4 py-1 sm:flex-row sm:gap-8 md:overflow-x-auto">
            <div className="w-full max-sm:overflow-x-auto">
                <div className="w-fit flex-1 text-sm font-medium whitespace-nowrap max-sm:mx-auto">
                    Showing {((page - 1) * perPage + 1).toLocaleString()} to{' '}
                    {Math.min(page * perPage, total).toLocaleString()} of{' '}
                    {total.toLocaleString()} results
                </div>
            </div>
            <div className="flex flex-col-reverse items-center gap-4 max-sm:w-full sm:flex-row sm:gap-6 lg:gap-8">
                <div className="max-sm:w-full max-sm:overflow-x-auto">
                    <div className="flex w-fit items-center space-x-2 max-sm:mx-auto">
                        <p className="text-sm font-medium whitespace-nowrap">
                            Rows per page
                        </p>
                        <Select
                            value={String(perPage)}
                            onValueChange={(value) =>
                                setParams((prevParams: Params) => ({
                                    ...prevParams,
                                    page: 1,
                                    per_page: Number(value),
                                }))
                            }
                        >
                            <SelectTrigger className="h-8 w-[4.5rem]">
                                <SelectValue placeholder="10" />
                            </SelectTrigger>
                            <SelectContent side="top">
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="max-sm:w-full max-sm:overflow-x-auto">
                    <Pagination className="w-fit">
                        <PaginationContent>
                            <PaginationItem>
                                <PaginationPrevious
                                    disabled={params.page === 1}
                                    onClick={handlePrevious}
                                />
                            </PaginationItem>
                            {renderPageNumbers()}
                            <PaginationItem>
                                <PaginationNext
                                    disabled={params.page === totalPageCount}
                                    onClick={handleNext}
                                />
                            </PaginationItem>
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>
        </div>
    );
}
