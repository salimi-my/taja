import type { Dispatch, SetStateAction } from 'react';
import { DatePicker } from '@/components/ui/date-picker';
import type { Params } from '@/types';

type DateParamKey =
    | 'as_of'
    | 'effective_from'
    | 'effective_to'
    | 'recorded_from'
    | 'recorded_to';

type ReportDateFilterProps = {
    label: string;
    params: Params;
    setParams: Dispatch<SetStateAction<Params>>;
    /** The param that holds the date, or the start of the range. */
    fromKey: DateParamKey;
    /** The param that holds the end of the range. Leave out for a single date. */
    toKey?: DateParamKey;
};

/**
 * A single date or a from-to range filter of a report, stored in the query
 * string like the other table filters.
 */
export function ReportDateFilter({
    label,
    params,
    setParams,
    fromKey,
    toKey,
}: ReportDateFilterProps) {
    const update = (key: DateParamKey, value: string) => {
        setParams((previous) => ({
            ...previous,
            page: 1,
            [key]: value || undefined,
        }));
    };

    return (
        <div className="flex items-center gap-2">
            <span className="text-sm whitespace-nowrap text-muted-foreground">
                {label}
            </span>
            <div className="w-40">
                <DatePicker
                    value={params[fromKey] ?? ''}
                    onChange={(value) => update(fromKey, value)}
                    placeholder={toKey ? 'From' : 'Pick a date'}
                />
            </div>
            {toKey && (
                <div className="w-40">
                    <DatePicker
                        value={params[toKey] ?? ''}
                        onChange={(value) => update(toKey, value)}
                        placeholder="To"
                    />
                </div>
            )}
        </div>
    );
}
