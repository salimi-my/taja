import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { ImportIssue } from '@/types';

type ImportIssuesTableProps = {
    issues: ImportIssue[];
    /** How many issues exist in total, when only the first ones are listed. */
    total: number;
};

export function ImportIssuesTable({ issues, total }: ImportIssuesTableProps) {
    return (
        <div className="flex flex-col gap-2">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-20">Line</TableHead>
                        <TableHead>Column</TableHead>
                        <TableHead>Message</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {issues.map((issue, index) => (
                        <TableRow key={`${issue.line}-${index}`}>
                            <TableCell>{issue.line}</TableCell>
                            <TableCell className="font-mono text-xs">
                                {issue.column ?? '-'}
                            </TableCell>
                            <TableCell className="whitespace-normal">
                                {issue.message}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
            {total > issues.length && (
                <p className="text-sm text-muted-foreground">
                    Showing the first {issues.length.toLocaleString()} of{' '}
                    {total.toLocaleString()}. Download the errors CSV to see
                    every row with a problem.
                </p>
            )}
        </div>
    );
}
