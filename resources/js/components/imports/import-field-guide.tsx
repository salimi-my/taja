import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { ImportColumnGuide } from '@/types';

type ImportFieldGuideProps = {
    columns: ImportColumnGuide[];
};

export function ImportFieldGuide({ columns }: ImportFieldGuideProps) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Column</TableHead>
                    <TableHead>Required</TableHead>
                    <TableHead>Format</TableHead>
                    <TableHead>Example</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {columns.map((column) => (
                    <TableRow key={column.header}>
                        <TableCell className="font-mono text-xs">
                            {column.header}
                        </TableCell>
                        <TableCell>
                            {column.required ? (
                                <Badge variant="secondary">Required</Badge>
                            ) : (
                                <span className="text-muted-foreground">
                                    Optional
                                </span>
                            )}
                        </TableCell>
                        <TableCell className="whitespace-normal">
                            {column.format}
                        </TableCell>
                        <TableCell className="font-mono text-xs">
                            {column.example === '' ? 'N/A' : column.example}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
