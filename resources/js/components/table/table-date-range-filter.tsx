import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import {
    type Dispatch,
    type HTMLAttributes,
    type SetStateAction,
    useCallback,
    useMemo,
    useState,
} from 'react';
import { DateRange } from 'react-day-picker';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { Params } from '@/types';

interface TableDateRangeFilterProps extends HTMLAttributes<HTMLDivElement> {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
}

export function TableDateRangeFilter({
    params,
    setParams,
    className,
}: TableDateRangeFilterProps) {
    const committedDate: DateRange | undefined = useMemo(
        () =>
            params.start_date || params.end_date
                ? {
                      from: params.start_date
                          ? new Date(params.start_date)
                          : undefined,
                      to: params.end_date
                          ? new Date(params.end_date)
                          : undefined,
                  }
                : undefined,
        [params.end_date, params.start_date],
    );
    const [draftDate, setDraftDate] = useState<DateRange | undefined>(
        undefined,
    );
    const [isOpen, setIsOpen] = useState(false);

    const displayedDate = isOpen ? (draftDate ?? committedDate) : committedDate;

    const updateParams = useCallback(
        (newDate: DateRange | undefined) => {
            setParams((prev: Params) => ({
                ...prev,
                page: 1,
                start_date: newDate?.from
                    ? format(newDate.from, 'yyyy-MM-dd')
                    : undefined,
                end_date: newDate?.to
                    ? format(newDate.to, 'yyyy-MM-dd')
                    : undefined,
            }));
        },
        [setParams],
    );

    const handleSelect = useCallback(
        (newDate: DateRange | undefined) => {
            setDraftDate(newDate);

            const hasCompleteRange = Boolean(newDate?.from && newDate?.to);
            const isCleared = !newDate?.from && !newDate?.to;

            if (!hasCompleteRange && !isCleared) {
                return;
            }

            updateParams(newDate);
        },
        [updateParams],
    );

    const handleOpenChange = useCallback(
        (open: boolean) => {
            setIsOpen(open);

            if (open) {
                setDraftDate(committedDate);
                return;
            }

            setDraftDate(undefined);
        },
        [committedDate],
    );

    return (
        <div className={cn('grid gap-2', className)}>
            <Popover open={isOpen} onOpenChange={handleOpenChange}>
                <PopoverTrigger asChild>
                    <Button
                        id="date"
                        variant="outline"
                        className={cn(
                            'w-[240px] justify-start text-left font-normal',
                            !displayedDate?.from && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon />
                        {displayedDate?.from ? (
                            displayedDate.to ? (
                                `${format(displayedDate.from, 'dd MMM yyyy')} - ${format(displayedDate.to, 'dd MMM yyyy')}`
                            ) : (
                                format(displayedDate.from, 'dd MMM yyyy')
                            )
                        ) : (
                            <span>Select date range</span>
                        )}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="range"
                        defaultMonth={displayedDate?.from}
                        selected={displayedDate}
                        onSelect={handleSelect}
                        numberOfMonths={2}
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
