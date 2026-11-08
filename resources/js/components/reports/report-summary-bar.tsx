import { TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';

type ReportSummaryBarProps = {
    /** What the report currently shows, e.g. the date and the number of rows. */
    children: ReactNode;
    /** A short note on how to read the report. */
    note?: ReactNode;
};

/** The bar above a report that states what is shown and how to read it. */
export function ReportSummaryBar({ children, note }: ReportSummaryBarProps) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 rounded-lg bg-muted/50 px-3 py-2 text-sm">
            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                {children}
            </div>
            {note && (
                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <TriangleAlert className="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                    {note}
                </p>
            )}
        </div>
    );
}
