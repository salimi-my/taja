import { router } from '@inertiajs/react';
import { CircleAlert, CircleCheckBig } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { ImportSample } from '@/types';

/** The rows each "show more" click adds, and the most rows that can be shown. */
export const SAMPLE_STEP = 10;
export const SAMPLE_MAX = 100;

/** Opaque backgrounds, so the sticky columns hide what scrolls under them. */
const validRow = 'bg-card hover:bg-muted';
const errorRow =
    'bg-[color-mix(in_oklab,var(--destructive)_6%,var(--card))] hover:bg-[color-mix(in_oklab,var(--destructive)_10%,var(--card))]';
const highlightedRow =
    'bg-[color-mix(in_oklab,var(--primary)_20%,var(--card))] hover:bg-[color-mix(in_oklab,var(--primary)_20%,var(--card))]';

type ImportSampleTableProps = {
    sample: ImportSample;
    /** How many rows the file has in total. */
    total: number;
    /** The line that was just jumped to, so it can be highlighted. */
    highlightLine?: number | null;
    /** Jumps to the errors of a line. */
    onViewErrors?: (line: number) => void;
};

/** The first rows of the file, each marked as valid or with its errors. */
export function ImportSampleTable({
    sample,
    total,
    highlightLine,
    onViewErrors,
}: ImportSampleTableProps) {
    const [loading, setLoading] = useState(false);
    const [errorsOnly, setErrorsOnly] = useState(false);
    const shown = sample.rows.length;
    const canShowMore = shown < Math.min(total, SAMPLE_MAX);
    const errorCount = sample.rows.filter(
        (row) => row.errors.length > 0,
    ).length;
    const rows = errorsOnly
        ? sample.rows.filter(
              (row) => row.errors.length > 0 || row.line === highlightLine,
          )
        : sample.rows;

    const showMore = () => {
        router.reload({
            data: { sample_rows: shown + SAMPLE_STEP },
            only: ['sample'],
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center justify-between gap-3 print:hidden">
                <p className="text-sm text-muted-foreground">
                    The first rows of the file, as they will be read. Check that
                    each value sits in the right column.
                </p>
                {errorCount > 0 && (
                    <div className="flex items-center gap-2">
                        <Switch
                            id="sample-errors-only"
                            checked={errorsOnly}
                            onCheckedChange={setErrorsOnly}
                        />
                        <Label htmlFor="sample-errors-only">Errors only</Label>
                    </div>
                )}
            </div>

            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted hover:bg-muted">
                            <TableHead className="sticky left-0 z-10 w-16 max-w-16 min-w-16 bg-muted pl-4">
                                Line
                            </TableHead>
                            <TableHead className="sticky left-16 z-10 w-28 min-w-28 bg-muted shadow-[1px_0_0_0_var(--border)]">
                                Result
                            </TableHead>
                            {sample.headers.map((header) => (
                                <TableHead
                                    key={header}
                                    className="font-mono text-xs"
                                >
                                    {header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => {
                            const hasErrors = row.errors.length > 0;

                            return (
                                <TableRow
                                    key={row.line}
                                    id={`sample-row-${row.line}`}
                                    className={cn(
                                        hasErrors ? errorRow : validRow,
                                        highlightLine === row.line &&
                                            highlightedRow,
                                    )}
                                >
                                    <TableCell className="sticky left-0 z-10 w-16 max-w-16 min-w-16 bg-inherit pl-4 text-muted-foreground tabular-nums">
                                        {row.line}
                                    </TableCell>
                                    <TableCell className="sticky left-16 z-10 w-28 min-w-28 bg-inherit shadow-[1px_0_0_0_var(--border)]">
                                        {hasErrors ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            onViewErrors?.(
                                                                row.line,
                                                            )
                                                        }
                                                        className="inline-flex items-center gap-1.5 rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive transition-colors hover:bg-destructive/20"
                                                    >
                                                        <CircleAlert className="size-3.5" />
                                                        {row.errors.length === 1
                                                            ? 'Error'
                                                            : `${row.errors.length} errors`}
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent className="max-w-sm">
                                                    {row.errors.join(' | ')}
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <CircleCheckBig className="size-3.5" />
                                                Valid
                                            </span>
                                        )}
                                    </TableCell>
                                    {sample.headers.map((header) => (
                                        <TableCell
                                            key={header}
                                            className={cn(
                                                'max-w-56 truncate',
                                                row.error_columns.includes(
                                                    header,
                                                ) &&
                                                    'font-medium text-destructive',
                                            )}
                                            title={
                                                row.data[header] ?? undefined
                                            }
                                        >
                                            {row.data[header] ?? (
                                                <span
                                                    className={
                                                        row.error_columns.includes(
                                                            header,
                                                        )
                                                            ? undefined
                                                            : 'text-muted-foreground'
                                                    }
                                                >
                                                    -
                                                </span>
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>

            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    Showing {rows.length.toLocaleString()} of{' '}
                    {total.toLocaleString()} rows.
                </p>
                {canShowMore && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="print:hidden"
                        disabled={loading}
                        onClick={showMore}
                    >
                        {loading && <Spinner className="size-4" />}
                        Show{' '}
                        {Math.min(
                            SAMPLE_STEP,
                            Math.min(total, SAMPLE_MAX) - shown,
                        )}{' '}
                        more
                    </Button>
                )}
            </div>
        </div>
    );
}
