import { Link } from '@inertiajs/react';
import { FileSpreadsheet, FileUp } from 'lucide-react';
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
import { Spinner } from '@/components/ui/spinner';
import { UserAvatarName } from '@/components/user-avatar-name';
import { importTypeIcons } from '@/lib/imports';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/imports';
import type { ImportType, RecentImport } from '@/types';
import { RelativeTime } from './relative-time';

type RecentImportsProps = {
    imports: RecentImport[];
};

/** Tint the status badge: finished green, failed red, still working with a spinner. */
const STATUS_CLASSES: Record<string, string> = {
    imported: 'bg-green-500/10 text-green-700 dark:text-green-400',
    failed: 'bg-destructive/10 text-destructive',
    previewed: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
};

/** The colour of the progress bar under each import. */
const BAR_CLASSES: Record<string, string> = {
    imported: 'bg-green-500',
    failed: 'bg-destructive',
};

const PROCESSING_STATUSES = ['validating', 'importing'];

/** The latest CSV imports, each linking to its preview or result. Admins only. */
export function RecentImports({ imports }: RecentImportsProps) {
    return (
        <Card className="h-full border-sidebar-border max-md:py-4">
            <CardHeader className="flex flex-row items-center justify-between gap-2 max-md:px-4">
                <div className="grid gap-1">
                    <CardTitle className="max-md:text-sm">
                        Recent imports
                    </CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        The latest CSV files uploaded
                    </CardDescription>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href={index()} prefetch>
                        View all
                    </Link>
                </Button>
            </CardHeader>
            <CardContent className="max-md:px-4">
                {imports.length === 0 ? (
                    <Empty className="border-0 p-4">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileUp />
                            </EmptyMedia>
                            <EmptyTitle>No imports yet</EmptyTitle>
                            <EmptyDescription>
                                Uploaded CSV files appear here.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ul className="grid gap-2">
                        {imports.map((item) => {
                            const TypeIcon =
                                importTypeIcons[item.type as ImportType] ??
                                FileSpreadsheet;
                            const progress =
                                item.total_rows > 0
                                    ? Math.min(
                                          100,
                                          (item.imported_count /
                                              item.total_rows) *
                                              100,
                                      )
                                    : 0;

                            return (
                                <li
                                    key={item.id}
                                    className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/40"
                                >
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                        <TypeIcon
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1 space-y-1.5">
                                        <div className="flex items-center justify-between gap-3">
                                            <Link
                                                href={show(item.id)}
                                                prefetch
                                                className="link-hover truncate text-sm font-semibold after:h-[2px]"
                                            >
                                                {item.original_filename}
                                            </Link>
                                            <Badge
                                                variant="secondary"
                                                className={cn(
                                                    'shrink-0',
                                                    STATUS_CLASSES[item.status],
                                                )}
                                            >
                                                {PROCESSING_STATUSES.includes(
                                                    item.status,
                                                ) && <Spinner />}
                                                {item.status_label}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div
                                                role="progressbar"
                                                aria-valuenow={Math.round(
                                                    progress,
                                                )}
                                                aria-valuemin={0}
                                                aria-valuemax={100}
                                                aria-label="Rows imported"
                                                className="h-1 flex-1 overflow-hidden rounded-full bg-muted"
                                            >
                                                <div
                                                    className={cn(
                                                        'h-full rounded-full bg-primary',
                                                        BAR_CLASSES[
                                                            item.status
                                                        ],
                                                    )}
                                                    style={{
                                                        width: `${progress}%`,
                                                    }}
                                                />
                                            </div>
                                            <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                                {item.imported_count.toLocaleString()}{' '}
                                                of{' '}
                                                {item.total_rows.toLocaleString()}{' '}
                                                rows
                                            </span>
                                        </div>
                                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <span className="font-medium">
                                                {item.type_label}
                                            </span>
                                            <span aria-hidden="true">·</span>
                                            {item.uploaded_by ? (
                                                <UserAvatarName
                                                    name={item.uploaded_by}
                                                    avatarUrl={
                                                        item.uploaded_by_avatar_url
                                                    }
                                                    size="compact"
                                                />
                                            ) : (
                                                'Unknown'
                                            )}
                                            <span aria-hidden="true">·</span>
                                            <RelativeTime
                                                iso={item.created_at}
                                                className="shrink-0"
                                            />
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
