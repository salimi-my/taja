import type { Dispatch, SetStateAction } from 'react';
import { DatePicker } from '@/components/ui/date-picker';
import type { Params } from '@/types';

type ReportDateFilterProps = {
    /** Names the filter for screen readers. */
    label: string;
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    /** The param that holds the date. */
    paramKey: 'as_of';
};

/**
 * A single date filter of a report, styled like the other table filters and
 * stored in the query string.
 */
export function ReportDateFilter({
    label,
    params,
    setParams,
    paramKey,
}: ReportDateFilterProps) {
    const value = params[paramKey];

    return (
        <div className="shrink-0">
            <DatePicker
                value={value ?? ''}
                onChange={(next) =>
                    setParams((previous) => ({
                        ...previous,
                        page: 1,
                        [paramKey]: next || undefined,
                    }))
                }
                placeholder="Pick a date"
                aria-label={value ? `${label}: ${value}` : label}
                className="h-8 w-auto border-dashed font-medium text-foreground"
            />
        </div>
    );
}
