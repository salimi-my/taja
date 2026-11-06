import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    CalendarClock,
    FilePen,
    FilePlus2,
    FileUp,
    Globe,
    History,
    Percent,
    UserMinus,
    UserPlus,
    Wrench,
} from 'lucide-react';
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
import { cn } from '@/lib/utils';
import { rightsHistory } from '@/routes/reports';
import { show } from '@/routes/works';
import type { RightsHistoryEntry } from '@/types';
import { RelativeTime } from './relative-time';

type RecentHistoryProps = {
    entries: RightsHistoryEntry[];
};

const GREEN = 'bg-green-500/10 text-green-700 dark:text-green-400';
const BLUE = 'bg-blue-500/10 text-blue-700 dark:text-blue-400';
const AMBER = 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
const RED = 'bg-destructive/10 text-destructive';
const NEUTRAL = 'bg-muted text-muted-foreground';

type ChangeTypeStyle = { icon: LucideIcon; classes: string };

/** The icon and tint of each change type: additions are green or blue, removals red, fixes amber. */
const CHANGE_TYPE_STYLES: Record<string, ChangeTypeStyle> = {
    work_created: { icon: FilePlus2, classes: GREEN },
    work_updated: { icon: FilePen, classes: NEUTRAL },
    party_added: { icon: UserPlus, classes: BLUE },
    party_removed: { icon: UserMinus, classes: RED },
    share_updated: { icon: Percent, classes: NEUTRAL },
    territory_changed: { icon: Globe, classes: NEUTRAL },
    dates_changed: { icon: CalendarClock, classes: NEUTRAL },
    correction: { icon: Wrench, classes: AMBER },
    imported: { icon: FileUp, classes: GREEN },
};

const FALLBACK_STYLE: ChangeTypeStyle = { icon: History, classes: NEUTRAL };

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
                            const { icon: Icon, classes } =
                                CHANGE_TYPE_STYLES[entry.change_type] ??
                                FALLBACK_STYLE;

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
