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

/** The params that can hold either end of a date range. */
type DateRangeParamKey =
    | 'start_date'
    | 'end_date'
    | 'effective_from'
    | 'effective_to'
    | 'recorded_from'
    | 'recorded_to';

interface TableDateRangeFilterProps extends HTMLAttributes<HTMLDivElement> {
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    /** The param holding the start of the range. Defaults to `start_date`. */
    startKey?: DateRangeParamKey;
    /** The param holding the end of the range. Defaults to `end_date`. */
    endKey?: DateRangeParamKey;
    /** The text shown while no range is picked. */
    placeholder?: string;
}

export function TableDateRangeFilter({
    params,
    setParams,
    startKey = 'start_date',
    endKey = 'end_date',
    placeholder = 'Select date range',
    className,
}: TableDateRangeFilterProps) {
    const startValue = params[startKey];
    const endValue = params[endKey];

    const committedDate: DateRange | undefined = useMemo(
        () =>
            startValue || endValue
                ? {
                      from: startValue ? new Date(startValue) : undefined,
                      to: endValue ? new Date(endValue) : undefined,
                  }
                : undefined,
        [endValue, startValue],
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
                [startKey]: newDate?.from
                    ? format(newDate.from, 'yyyy-MM-dd')
                    : undefined,
                [endKey]: newDate?.to
                    ? format(newDate.to, 'yyyy-MM-dd')
                    : undefined,
            }));
        },
        [endKey, setParams, startKey],
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
                        size="sm"
                        className="h-8 w-[240px] justify-start border-dashed text-left"
                    >
                        <CalendarIcon />
                        {displayedDate?.from ? (
                            displayedDate.to ? (
                                `${format(displayedDate.from, 'dd MMM yyyy')} - ${format(displayedDate.to, 'dd MMM yyyy')}`
                            ) : (
                                format(displayedDate.from, 'dd MMM yyyy')
                            )
                        ) : (
                            <span>{placeholder}</span>
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
                        captionLayout="dropdown"
                        startMonth={new Date(1900, 0)}
                        endMonth={new Date(new Date().getFullYear() + 30, 11)}
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
