import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, Earth, FileSearch } from 'lucide-react';
import { memo } from 'react';
import {
    PublisherStatusBadge,
    RightsTypeBadges,
} from '@/components/publishers/publisher-badges';
import { PublisherCellActions } from '@/components/publishers/publisher-cell-actions';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { formatTerritories } from '@/lib/publishers';
import { show } from '@/routes/publishers';
import type { Publisher } from '@/types';

type PublisherCardRowItemProps = {
    publisher: Publisher;
    rightsTypeLabels: Record<string, string>;
};

const PublisherCardRowItem = memo(function PublisherCardRowItem({
    publisher,
    rightsTypeLabels,
}: PublisherCardRowItemProps) {
    return (
        <div className="flex flex-col gap-2 rounded-lg border p-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                    <Link
                        href={show(publisher.id)}
                        className="block truncate text-sm font-semibold"
                    >
                        {publisher.name}
                    </Link>
                    <p className="font-mono text-xs text-muted-foreground">
                        {publisher.publisher_code} · {publisher.society_cmo}
                    </p>
                </div>
                <PublisherCellActions publisher={publisher} />
            </div>

            <RightsTypeBadges
                rightsTypes={publisher.rights_types_granted}
                labels={rightsTypeLabels}
            />

            <div className="flex flex-wrap items-center gap-2">
                <PublisherStatusBadge
                    publisher={publisher}
                    className="px-1.5 text-[10px] leading-none"
                />
                <Badge
                    variant="secondary"
                    className="text-[10px] font-normal text-nowrap"
                >
                    <Earth className="size-3" />
                    {formatTerritories(publisher.territories_granted)}
                </Badge>
                <Badge
                    variant="secondary"
                    className="text-[10px] font-normal text-nowrap"
                >
                    <Calendar className="size-3" />
                    {format(
                        new Date(publisher.agreement_start_date),
                        'dd MMM yyyy',
                    )}
                </Badge>
            </div>
        </div>
    );
});

type PublishersCardRowProps = {
    publishers: Publisher[];
    rightsTypeLabels: Record<string, string>;
};

export function PublishersCardRow({
    publishers,
    rightsTypeLabels,
}: PublishersCardRowProps) {
    if (publishers.length === 0) {
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
            {publishers.map((publisher) => (
                <PublisherCardRowItem
                    key={publisher.id}
                    publisher={publisher}
                    rightsTypeLabels={rightsTypeLabels}
                />
            ))}
        </div>
    );
}
