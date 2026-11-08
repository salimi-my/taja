import { CircleCheck, TriangleAlert } from 'lucide-react';
import { TableCell } from '@/components/ui/table';
import { formatTotal, isCompleteTotal } from '@/lib/reports';
import { cn } from '@/lib/utils';

type ShareTotalCellProps = {
    total: number | null;
    /** Shows a dash instead of the total, e.g. for a work without parties. */
    hasParties?: boolean;
};

/**
 * A share total in a report table. A total other than 100% gets a warning
 * icon, so the state does not rely on color alone.
 */
export function ShareTotalCell({
    total,
    hasParties = true,
}: ShareTotalCellProps) {
    if (total === null || !hasParties) {
        return (
            <TableCell className="text-right text-muted-foreground">
                -
            </TableCell>
        );
    }

    const isComplete = isCompleteTotal(total);

    return (
        <TableCell
            className={cn(
                'text-right font-semibold tabular-nums',
                !isComplete && 'text-amber-600 dark:text-amber-400',
            )}
        >
            <span className="inline-flex items-center justify-end gap-1">
                {isComplete ? (
                    <CircleCheck className="size-3.5 text-green-600 dark:text-green-400" />
                ) : (
                    <TriangleAlert className="size-3.5" />
                )}
                {formatTotal(total)}
            </span>
        </TableCell>
    );
}
