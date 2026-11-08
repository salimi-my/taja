import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { FileSearch } from 'lucide-react';
import { memo } from 'react';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TableCell, TableRow } from '@/components/ui/table';
import { WriterStatusBadge } from '@/components/writers/writer-badges';
import { WriterCellActions } from '@/components/writers/writer-cell-actions';
import { show as showPublisher } from '@/routes/publishers';
import { show } from '@/routes/writers';
import type { Writer } from '@/types';

type WriterTableRowItemProps = {
    writer: Writer;
};

const WriterTableRowItem = memo(function WriterTableRowItem({
    writer,
}: WriterTableRowItemProps) {
    return (
        <TableRow>
            <TableCell className="font-mono text-xs">
                {writer.writer_code}
            </TableCell>
            <TableCell>
                <Link
                    href={show(writer.id)}
                    className="link-hover font-semibold after:h-[2px]"
                >
                    {writer.name}
                </Link>
                {writer.aliases.length > 0 && (
                    <p className="text-xs text-muted-foreground">
                        {writer.aliases.map((alias) => alias.name).join(', ')}
                    </p>
                )}
            </TableCell>
            <TableCell>{writer.cmo_society}</TableCell>
            <TableCell className="font-mono text-xs">
                {writer.ipi_name_number ?? 'N/A'}
            </TableCell>
            <TableCell>
                {writer.publisher ? (
                    <Link
                        href={showPublisher(writer.publisher.id)}
                        className="link-hover after:h-[2px]"
                    >
                        {writer.publisher.name}
                    </Link>
                ) : (
                    <span className="text-muted-foreground">N/A</span>
                )}
            </TableCell>
            <TableCell>
                <WriterStatusBadge writer={writer} />
            </TableCell>
            <TableCell>
                {format(new Date(writer.agreement_start_date), 'dd MMM yyyy')}
            </TableCell>
            <TableCell>
                <WriterCellActions writer={writer} />
            </TableCell>
        </TableRow>
    );
});

type WritersTableRowProps = {
    writers: Writer[];
};

export function WritersTableRow({ writers }: WritersTableRowProps) {
    if (writers.length === 0) {
        return (
            <TableRow className="hover:bg-transparent">
                <TableCell colSpan={999}>
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileSearch />
                            </EmptyMedia>
                            <EmptyTitle>No results found</EmptyTitle>
                            <EmptyDescription>
                                Try adjusting your search or filters to find
                                what you're looking for.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </TableCell>
            </TableRow>
        );
    }

    return (
        <>
            {writers.map((writer) => (
                <WriterTableRowItem key={writer.id} writer={writer} />
            ))}
        </>
    );
}
