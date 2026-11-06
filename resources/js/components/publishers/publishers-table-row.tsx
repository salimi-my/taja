import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { FileSearch } from 'lucide-react';
import { memo } from 'react';
import {
    PublisherStatusBadge,
    RightsTypeBadges,
} from '@/components/publishers/publisher-badges';
import { PublisherCellActions } from '@/components/publishers/publisher-cell-actions';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TableCell, TableRow } from '@/components/ui/table';
import { formatTerritories } from '@/lib/publishers';
import { show } from '@/routes/publishers';
import type { Publisher } from '@/types';

type PublisherTableRowItemProps = {
    publisher: Publisher;
    rightsTypeLabels: Record<string, string>;
};

const PublisherTableRowItem = memo(function PublisherTableRowItem({
    publisher,
    rightsTypeLabels,
}: PublisherTableRowItemProps) {
    return (
        <TableRow>
            <TableCell className="font-mono text-xs">
                {publisher.publisher_code}
            </TableCell>
            <TableCell>
                <Link
                    href={show(publisher.id)}
                    className="link-hover font-semibold after:h-[2px]"
                >
                    {publisher.name}
                </Link>
            </TableCell>
            <TableCell>{publisher.society_cmo}</TableCell>
            <TableCell>
                <RightsTypeBadges
                    rightsTypes={publisher.rights_types_granted}
                    labels={rightsTypeLabels}
                />
            </TableCell>
            <TableCell>
                {formatTerritories(publisher.territories_granted)}
            </TableCell>
            <TableCell>
                <PublisherStatusBadge publisher={publisher} />
            </TableCell>
            <TableCell>
                {format(
                    new Date(publisher.agreement_start_date),
                    'dd MMM yyyy',
                )}
            </TableCell>
            <TableCell>
                <PublisherCellActions publisher={publisher} />
            </TableCell>
        </TableRow>
    );
});

type PublishersTableRowProps = {
    publishers: Publisher[];
    rightsTypeLabels: Record<string, string>;
};

export function PublishersTableRow({
    publishers,
    rightsTypeLabels,
}: PublishersTableRowProps) {
    if (publishers.length === 0) {
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
            {publishers.map((publisher) => (
                <PublisherTableRowItem
                    key={publisher.id}
                    publisher={publisher}
                    rightsTypeLabels={rightsTypeLabels}
                />
            ))}
        </>
    );
}
