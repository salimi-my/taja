import { router } from '@inertiajs/react';
import { CircleAlert, CircleCheckBig } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
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
import type { ImportSample } from '@/types';

/** The rows each "show more" click adds, and the most rows that can be shown. */
const SAMPLE_STEP = 10;
const SAMPLE_MAX = 100;

type ImportSampleTableProps = {
    sample: ImportSample;
    /** How many rows the file has in total. */
    total: number;
};

/** The first rows of the file, each marked as valid or with its errors. */
export function ImportSampleTable({ sample, total }: ImportSampleTableProps) {
    const [loading, setLoading] = useState(false);
    const shown = sample.rows.length;
    const canShowMore = shown < Math.min(total, SAMPLE_MAX);

    const showMore = () => {
        router.reload({
            data: { sample_rows: shown + SAMPLE_STEP },
            only: ['sample'],
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <div className="flex flex-col gap-2">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">Line</TableHead>
                        <TableHead className="w-24">Result</TableHead>
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
                    {sample.rows.map((row) => {
                        const hasErrors = row.errors.length > 0;

                        return (
                            <TableRow
                                key={row.line}
                                className={hasErrors ? 'bg-destructive/5' : ''}
                            >
                                <TableCell className="text-muted-foreground tabular-nums">
                                    {row.line}
                                </TableCell>
                                <TableCell>
                                    {hasErrors ? (
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span className="inline-flex cursor-default items-center gap-1 text-destructive">
                                                    <CircleAlert className="size-4" />
                                                    Error
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent className="max-w-sm">
                                                {row.errors.join(' | ')}
                                            </TooltipContent>
                                        </Tooltip>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <CircleCheckBig className="size-4" />
                                            Valid
                                        </span>
                                    )}
                                </TableCell>
                                {sample.headers.map((header) => (
                                    <TableCell
                                        key={header}
                                        className="max-w-56 truncate"
                                        title={row.data[header] ?? undefined}
                                    >
                                        {row.data[header] ?? (
                                            <span className="text-muted-foreground">
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
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    Showing {shown.toLocaleString()} of {total.toLocaleString()}{' '}
                    rows.
                </p>
                {canShowMore && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
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
