import { ArrowRight, CornerDownRight, EyeOff } from 'lucide-react';
import { useMemo } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { cn } from '@/lib/utils';
import type { ImportIssue } from '@/types';

type ImportIssuesTableProps = {
    issues: ImportIssue[];
    /** How many issues exist in total, when only the first ones are listed. */
    total: number;
    tone: 'error' | 'warning';
    /** Whether a line can be shown in the sample rows, loading them when needed. */
    canViewRow?: (line: number) => boolean;
    /** The line whose sample rows are being loaded. */
    loadingLine?: number | null;
    /** The most rows the preview can show, for the tooltip on later lines. */
    previewLimit?: number;
    /** The line that was just jumped to, so it can be highlighted. */
    highlightLine?: number | null;
    onViewRow?: (line: number) => void;
};

type IssueGroup = {
    line: number;
    issues: ImportIssue[];
    /** Lines that are only skipped because this line, in the same work, has an error. */
    skippedLines: number[];
};

/**
 * The server marks the other rows of a work as skipped when one of its rows
 * fails. These are consequences, not problems of their own.
 */
function isCascadeIssue(issue: ImportIssue): boolean {
    return issue.column === null && issue.message.startsWith('Skipped because');
}

/** The line that caused a skipped row, read from "Skipped because line N of this work has an error." */
function causeLineOf(issues: ImportIssue[]): number | null {
    const match = issues[0]?.message.match(/^Skipped because line (\d+)/);

    return match ? Number(match[1]) : null;
}

/**
 * One group per line. Skipped rows are attached to the line that caused them,
 * so it stays clear which rows belong together.
 */
function groupByLine(issues: ImportIssue[]): IssueGroup[] {
    const byLine = new Map<number, ImportIssue[]>();

    for (const issue of issues) {
        byLine.set(issue.line, [...(byLine.get(issue.line) ?? []), issue]);
    }

    const groups: IssueGroup[] = [];
    const cascades: { line: number; cause: number | null }[] = [];

    for (const [line, lineIssues] of byLine) {
        if (lineIssues.every(isCascadeIssue)) {
            cascades.push({ line, cause: causeLineOf(lineIssues) });
        } else {
            groups.push({ line, issues: lineIssues, skippedLines: [] });
        }
    }

    for (const cascade of cascades) {
        const cause = groups.find((group) => group.line === cascade.cause);

        if (cause) {
            cause.skippedLines.push(cascade.line);
        } else {
            groups.push({
                line: cascade.line,
                issues: byLine.get(cascade.line) ?? [],
                skippedLines: [],
            });
        }
    }

    return groups.sort((first, second) => first.line - second.line);
}

function IssueRow({
    group,
    tone,
    canView,
    isLoading,
    previewLimit,
    showsViewColumn,
    highlighted,
    highlightLine,
    onViewRow,
}: {
    group: IssueGroup;
    tone: ImportIssuesTableProps['tone'];
    canView: boolean;
    isLoading: boolean;
    previewLimit?: number;
    showsViewColumn: boolean;
    highlighted: boolean;
    highlightLine?: number | null;
    onViewRow?: (line: number) => void;
}) {
    return (
        <TableRow
            id={`issue-line-${group.line}`}
            className={cn(
                'align-top',
                highlighted && 'bg-primary/5 hover:bg-primary/5',
            )}
        >
            <TableCell className="w-24 py-3 pl-4">
                <div className="flex items-center gap-2">
                    <span
                        className={cn(
                            'size-1.5 rounded-full',
                            tone === 'error'
                                ? 'bg-destructive'
                                : 'bg-amber-500',
                        )}
                    />
                    <span className="font-medium tabular-nums">
                        {group.line === 1 ? 'Header' : group.line}
                    </span>
                </div>
            </TableCell>
            <TableCell className="py-3 whitespace-normal">
                <ul className="flex flex-col gap-2">
                    {group.issues.map((issue, index) => (
                        <li
                            key={`${issue.column}-${index}`}
                            className="flex flex-wrap items-baseline gap-x-2 gap-y-1"
                        >
                            {isCascadeIssue(issue) ? (
                                <CornerDownRight className="size-3.5 translate-y-0.5 text-muted-foreground" />
                            ) : issue.column ? (
                                <Badge
                                    variant="secondary"
                                    className="font-mono"
                                >
                                    {issue.column}
                                </Badge>
                            ) : (
                                <Badge variant="outline">Whole row</Badge>
                            )}
                            <span
                                className={cn(
                                    'min-w-0 flex-1 basis-64',
                                    isCascadeIssue(issue) &&
                                        'text-muted-foreground',
                                )}
                            >
                                {issue.message}
                            </span>
                        </li>
                    ))}
                </ul>
                {group.skippedLines.length > 0 && (
                    <div className="mt-3 flex items-start gap-2 border-t border-dashed pt-3 text-muted-foreground">
                        <CornerDownRight className="mt-1 size-3.5 shrink-0" />
                        <div className="flex shrink-0 flex-wrap gap-1.5">
                            {group.skippedLines.map((line) => (
                                <Badge
                                    key={line}
                                    id={`issue-line-${line}`}
                                    variant="outline"
                                    className={cn(
                                        'tabular-nums',
                                        highlightLine === line &&
                                            'border-primary bg-primary/10 text-primary',
                                    )}
                                >
                                    Line {line}
                                </Badge>
                            ))}
                        </div>
                        <span className="min-w-0 flex-1">
                            {group.skippedLines.length === 1
                                ? 'This row is also skipped because it belongs to the same work.'
                                : 'These rows are also skipped because they belong to the same work.'}
                        </span>
                    </div>
                )}
            </TableCell>
            <TableCell className="w-36 py-2 pr-3 text-right">
                {canView ? (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={isLoading}
                        onClick={() => onViewRow?.(group.line)}
                    >
                        View row
                        {isLoading ? <Spinner /> : <ArrowRight />}
                    </Button>
                ) : (
                    showsViewColumn &&
                    group.line > 1 && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span className="inline-flex cursor-default items-center gap-1.5 px-2 text-xs text-muted-foreground">
                                    <EyeOff className="size-4" />
                                    Not in preview
                                </span>
                            </TooltipTrigger>
                            <TooltipContent className="max-w-xs">
                                Only the first {previewLimit} rows can be
                                previewed. Check this line in the file.
                            </TooltipContent>
                        </Tooltip>
                    )
                )}
            </TableCell>
        </TableRow>
    );
}

/** The problems found in a file, one row per line of the file. */
export function ImportIssuesTable({
    issues,
    total,
    tone,
    canViewRow,
    loadingLine,
    previewLimit,
    highlightLine,
    onViewRow,
}: ImportIssuesTableProps) {
    const groups = useMemo(() => groupByLine(issues), [issues]);

    return (
        <div className="flex flex-col gap-3">
            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader className="bg-muted/40">
                        <TableRow className="hover:bg-transparent">
                            <TableHead className="pl-4">Line</TableHead>
                            <TableHead>Problem</TableHead>
                            <TableHead className="w-36" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {groups.map((group) => (
                            <IssueRow
                                key={group.line}
                                group={group}
                                tone={tone}
                                canView={canViewRow?.(group.line) ?? false}
                                isLoading={loadingLine === group.line}
                                previewLimit={previewLimit}
                                showsViewColumn={canViewRow !== undefined}
                                highlighted={highlightLine === group.line}
                                highlightLine={highlightLine}
                                onViewRow={onViewRow}
                            />
                        ))}
                    </TableBody>
                </Table>
            </div>

            <p className="text-sm text-muted-foreground">
                {total > issues.length ? (
                    <>
                        Showing the first {issues.length.toLocaleString()} of{' '}
                        {total.toLocaleString()}. Download the errors CSV to see
                        every row with a problem.
                    </>
                ) : (
                    <>
                        {groups.length.toLocaleString()}{' '}
                        {groups.length === 1 ? 'line' : 'lines'} listed.
                    </>
                )}
            </p>
        </div>
    );
}
