import { Link } from '@inertiajs/react';
import { FileSearch } from 'lucide-react';
import { memo } from 'react';
import { formatDate } from '@/components/detail-grid';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TableCell, TableRow } from '@/components/ui/table';
import { WorkRegistrationBadge } from '@/components/works/work-badges';
import { WorkCellActions } from '@/components/works/work-cell-actions';
import { show } from '@/routes/works';
import type { Work } from '@/types';

type WorkTableRowItemProps = {
    work: Work;
};

/** The contributors of a work as short text, e.g. `Jane Doe, John Doe +2`. */
export function formatContributors(work: Work, limit = 3): string {
    const names = [...new Set((work.contributors ?? []).map((c) => c.name))];

    if (names.length === 0) {
        return '';
    }

    const shown = names.slice(0, limit).join(', ');

    return names.length > limit ? `${shown} +${names.length - limit}` : shown;
}

const WorkTableRowItem = memo(function WorkTableRowItem({
    work,
}: WorkTableRowItemProps) {
    return (
        <TableRow>
            <TableCell className="font-mono text-xs">
                {work.tap_number}
            </TableCell>
            <TableCell className="font-mono text-xs">
                {work.macp_work_id}
            </TableCell>
            <TableCell className="max-w-[18rem]">
                <Link
                    href={show(work.id)}
                    className="link-hover font-semibold after:h-[2px]"
                >
                    {work.title}
                </Link>
                {work.english_title && work.other_title && (
                    <p className="truncate text-xs text-muted-foreground">
                        {work.other_title}
                    </p>
                )}
            </TableCell>
            <TableCell className="max-w-[16rem] truncate text-sm">
                {formatContributors(work) || (
                    <span className="text-muted-foreground">N/A</span>
                )}
            </TableCell>
            <TableCell>
                <WorkRegistrationBadge work={work} />
            </TableCell>
            <TableCell>{formatDate(work.registration_date) ?? 'N/A'}</TableCell>
            <TableCell>
                <WorkCellActions work={work} />
            </TableCell>
        </TableRow>
    );
});

type WorksTableRowProps = {
    works: Work[];
};

export function WorksTableRow({ works }: WorksTableRowProps) {
    if (works.length === 0) {
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
            {works.map((work) => (
                <WorkTableRowItem key={work.id} work={work} />
            ))}
        </>
    );
}
