import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { Params } from '@/types';

interface TableColumnHeaderProps extends React.HTMLAttributes<HTMLDivElement> {
    title: string;
    column: string;
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
}

export function TableColumnHeader({
    title,
    column,
    params,
    setParams,
    className,
}: TableColumnHeaderProps) {
    const handleSorting = (sortValue: string) => {
        setParams((prevParams: Params) => ({
            ...prevParams,
            sort: sortValue,
        }));
    };

    return (
        <div className={cn('flex items-center space-x-2', className)}>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        aria-label={`Sort by ${title}`}
                        variant="ghost"
                        size="sm"
                        className="-ml-3 h-8 text-sm data-[state=open]:bg-accent"
                    >
                        <span>{title}</span>
                        {params.sort === `${column}.desc` ? (
                            <ArrowDown />
                        ) : params.sort === `${column}.asc` ? (
                            <ArrowUp />
                        ) : (
                            <ChevronsUpDown />
                        )}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                    <DropdownMenuItem
                        aria-label="Sort Ascending"
                        onClick={() => handleSorting(`${column}.asc`)}
                    >
                        <ArrowUp className="size-3.5 text-muted-foreground/70" />
                        Asc
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        aria-label="Sort Descending"
                        onClick={() => handleSorting(`${column}.desc`)}
                    >
                        <ArrowDown className="size-3.5 text-muted-foreground/70" />
                        Desc
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
