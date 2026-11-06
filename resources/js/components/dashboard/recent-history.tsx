import { Link } from '@inertiajs/react';
import { History } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { rightsHistory } from '@/routes/reports';
import { show } from '@/routes/works';
import type { RightsHistoryEntry } from '@/types';

type RecentHistoryProps = {
    entries: RightsHistoryEntry[];
};

/** The latest recorded changes to the rights of works. */
export function RecentHistory({ entries }: RecentHistoryProps) {
    return (
        <Card className="h-full border-sidebar-border max-md:py-4">
            <CardHeader className="flex flex-row items-center justify-between gap-2 max-md:px-4">
                <div className="grid gap-1">
                    <CardTitle className="max-md:text-sm">
                        Recent rights changes
                    </CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        The latest entries of the rights history
                    </CardDescription>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href={rightsHistory()}>View all</Link>
                </Button>
            </CardHeader>
            <CardContent className="max-md:px-4">
                {entries.length === 0 ? (
                    <Empty className="border-0 p-4">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <History />
                            </EmptyMedia>
                            <EmptyTitle>No changes yet</EmptyTitle>
                            <EmptyDescription>
                                Changes to works and their parties appear here.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ul className="divide-y">
                        {entries.map((entry) => (
                            <li
                                key={entry.id}
                                className="flex flex-col gap-1 py-3 first:pt-0 last:pb-0"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {entry.change_type_label}
                                    </Badge>
                                    {entry.work && (
                                        <Link
                                            href={show(entry.work.id)}
                                            className="link-hover text-sm font-semibold after:h-[2px]"
                                        >
                                            {entry.work.tap_number}{' '}
                                            {entry.work.title}
                                        </Link>
                                    )}
                                </div>
                                <p className="text-sm">{entry.summary}</p>
                                <p className="text-xs text-muted-foreground">
                                    Effective {formatDate(entry.effective_date)}
                                    , recorded {formatDate(entry.recorded_at)}{' '}
                                    by{' '}
                                    {entry.user_name ??
                                        (entry.csv_import_id
                                            ? 'an import'
                                            : 'the system')}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
