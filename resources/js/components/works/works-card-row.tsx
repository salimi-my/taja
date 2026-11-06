import { Link } from '@inertiajs/react';
import { Calendar, FileSearch, Users } from 'lucide-react';
import { memo } from 'react';
import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { WorkRegistrationBadge } from '@/components/works/work-badges';
import { WorkCellActions } from '@/components/works/work-cell-actions';
import { formatContributors } from '@/components/works/works-table-row';
import { show } from '@/routes/works';
import type { Work } from '@/types';

type WorkCardRowItemProps = {
    work: Work;
};

const WorkCardRowItem = memo(function WorkCardRowItem({
    work,
}: WorkCardRowItemProps) {
    const contributors = formatContributors(work);

    return (
        <div className="flex flex-col gap-2 rounded-lg border p-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                    <Link
                        href={show(work.id)}
                        className="block truncate text-sm font-semibold"
                    >
                        {work.title}
                    </Link>
                    <p className="font-mono text-xs text-muted-foreground">
                        {work.tap_number} · {work.macp_work_id}
                    </p>
                    {contributors && (
                        <p className="truncate text-xs text-muted-foreground">
                            {contributors}
                        </p>
                    )}
                </div>
                <WorkCellActions work={work} />
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <WorkRegistrationBadge
                    work={work}
                    className="px-1.5 text-[10px] leading-none"
                />
                {work.parties_count !== undefined && (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-normal text-nowrap"
                    >
                        <Users className="size-3" />
                        {work.parties_count} parties
                    </Badge>
                )}
                {work.registration_date && (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-normal text-nowrap"
                    >
                        <Calendar className="size-3" />
                        {formatDate(work.registration_date)}
                    </Badge>
                )}
            </div>
        </div>
    );
});

type WorksCardRowProps = {
    works: Work[];
};

export function WorksCardRow({ works }: WorksCardRowProps) {
    if (works.length === 0) {
        return (
            <Empty className="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <FileSearch />
                    </EmptyMedia>
                    <EmptyTitle>No results found</EmptyTitle>
                    <EmptyDescription>
                        Try adjusting your search or filters to find what you're
                        looking for.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="space-y-3">
            {works.map((work) => (
                <WorkCardRowItem key={work.id} work={work} />
            ))}
        </div>
    );
}
