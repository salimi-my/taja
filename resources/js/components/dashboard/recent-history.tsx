import { Link } from '@inertiajs/react';
import { History } from 'lucide-react';
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
import { UserAvatarName } from '@/components/user-avatar-name';
import { changeTypeStyle } from '@/components/works/change-type-style';
import { cn } from '@/lib/utils';
import { rightsHistory } from '@/routes/reports';
import { show } from '@/routes/works';
import type { RightsHistoryEntry } from '@/types';
import { RelativeTime } from './relative-time';

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
                    <Link href={rightsHistory()} prefetch>
                        View all
                    </Link>
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
                    <ul className="grid gap-2">
                        {entries.map((entry) => {
                            const { icon: Icon, classes } = changeTypeStyle(
                                entry.change_type,
                            );

                            return (
                                <li
                                    key={entry.id}
                                    className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/40"
                                >
                                    <span
                                        className={cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-md',
                                            classes,
                                        )}
                                    >
                                        <Icon
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex items-baseline justify-between gap-3">
                                            {entry.work ? (
                                                <Link
                                                    href={show(entry.work.id)}
                                                    prefetch
                                                    className="min-w-0 truncate text-sm font-semibold"
                                                >
                                                    <span className="link-hover after:h-[2px]">
                                                        {entry.work.title}
                                                    </span>
                                                    <span className="ml-2 font-mono text-[11px] font-normal text-muted-foreground">
                                                        {entry.work.tap_number}
                                                    </span>
                                                </Link>
                                            ) : (
                                                <span />
                                            )}
                                            <RelativeTime
                                                iso={entry.recorded_at}
                                                className="shrink-0 text-xs text-muted-foreground"
                                            />
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {entry.summary}
                                        </p>
                                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <span
                                                className={cn(
                                                    'rounded-sm px-1.5 py-0.5 font-medium',
                                                    classes,
                                                )}
                                            >
                                                {entry.change_type_label}
                                            </span>
                                            <span aria-hidden="true">·</span>
                                            {entry.user_name ? (
                                                <UserAvatarName
                                                    name={entry.user_name}
                                                    avatarUrl={
                                                        entry.user_avatar_url
                                                    }
                                                    size="compact"
                                                />
                                            ) : entry.csv_import_id ? (
                                                'An import'
                                            ) : (
                                                'The system'
                                            )}
                                        </p>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
