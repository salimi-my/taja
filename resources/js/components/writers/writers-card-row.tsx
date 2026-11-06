import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Building2, Calendar, FileSearch } from 'lucide-react';
import { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { WriterStatusBadge } from '@/components/writers/writer-badges';
import { WriterCellActions } from '@/components/writers/writer-cell-actions';
import { show } from '@/routes/writers';
import type { Writer } from '@/types';

type WriterCardRowItemProps = {
    writer: Writer;
};

const WriterCardRowItem = memo(function WriterCardRowItem({
    writer,
}: WriterCardRowItemProps) {
    return (
        <div className="flex flex-col gap-2 rounded-lg border p-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                    <Link
                        href={show(writer.id)}
                        className="block truncate text-sm font-semibold"
                    >
                        {writer.name}
                    </Link>
                    <p className="font-mono text-xs text-muted-foreground">
                        {writer.writer_code} · {writer.cmo_society}
                    </p>
                    {writer.aliases.length > 0 && (
                        <p className="truncate text-xs text-muted-foreground">
                            {writer.aliases
                                .map((alias) => alias.name)
                                .join(', ')}
                        </p>
                    )}
                </div>
                <WriterCellActions writer={writer} />
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <WriterStatusBadge
                    writer={writer}
                    className="px-1.5 text-[10px] leading-none"
                />
                {writer.publisher && (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-normal text-nowrap"
                    >
                        <Building2 className="size-3" />
                        {writer.publisher.name}
                    </Badge>
                )}
                <Badge
                    variant="secondary"
                    className="text-[10px] font-normal text-nowrap"
                >
                    <Calendar className="size-3" />
                    {format(
                        new Date(writer.agreement_start_date),
                        'dd MMM yyyy',
                    )}
                </Badge>
            </div>
        </div>
    );
});

type WritersCardRowProps = {
    writers: Writer[];
};

export function WritersCardRow({ writers }: WritersCardRowProps) {
    if (writers.length === 0) {
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
            {writers.map((writer) => (
                <WriterCardRowItem key={writer.id} writer={writer} />
            ))}
        </div>
    );
}
