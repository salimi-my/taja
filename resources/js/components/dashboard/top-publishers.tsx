import { Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { show } from '@/routes/publishers';
import type { TopPublisher } from '@/types';
import { TopPublishersSkeleton } from './dashboard-skeletons';

type TopPublishersProps = {
    /** Undefined while the deferred data is still loading. */
    data: TopPublisher[] | undefined;
};

/** The publishers that are a current party of the most works. */
export function TopPublishers({ data }: TopPublishersProps) {
    if (data === undefined) {
        return <TopPublishersSkeleton />;
    }

    const largest = Math.max(1, ...data.map((item) => item.total));

    return (
        <Card className="flex h-full flex-col border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">Top publishers</CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    Publishers on the most works today
                </CardDescription>
            </CardHeader>
            <CardContent className="flex-1 max-md:px-4">
                {data.length === 0 ? (
                    <Empty className="h-full border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Building2 />
                            </EmptyMedia>
                            <EmptyTitle>No publishers on works yet</EmptyTitle>
                            <EmptyDescription>
                                Publishers with works in the catalogue will be
                                ranked here.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ol className="space-y-3">
                        {data.map((item, index) => (
                            <li key={item.id}>
                                <Link
                                    href={show(item.id)}
                                    prefetch
                                    className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50"
                                >
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold tabular-nums">
                                        {index + 1}
                                    </span>
                                    <div className="min-w-0 flex-1 space-y-2">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="truncate text-sm font-medium">
                                                {item.name}
                                            </span>
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0 px-1.5 text-[10px] leading-none font-normal"
                                            >
                                                {item.total.toLocaleString()}{' '}
                                                {item.total === 1
                                                    ? 'work'
                                                    : 'works'}
                                            </Badge>
                                        </div>
                                        <div
                                            aria-hidden="true"
                                            className="h-1.5 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                className="h-full rounded-full bg-chart-2"
                                                style={{
                                                    width: `${(item.total / largest) * 100}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
