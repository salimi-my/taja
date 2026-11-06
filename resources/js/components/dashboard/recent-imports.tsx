import { Link } from '@inertiajs/react';
import { FileUp } from 'lucide-react';
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
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { show } from '@/routes/imports';
import type { RecentImport } from '@/types';
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

const PROCESSING_STATUSES = ['validating', 'importing'];

/** The latest CSV imports, each linking to its preview or result. Admins only. */
export function RecentImports({ imports }: RecentImportsProps) {
    return (
        <Card className="h-full border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">Recent imports</CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    The latest CSV files uploaded
                </CardDescription>
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
                    <ul className="divide-y">
                        {imports.map((item) => (
                            <li
                                key={item.id}
                                className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div className="min-w-0">
                                    <Link
                                        href={show(item.id)}
                                        prefetch
                                        className="link-hover block truncate text-sm font-semibold after:h-[2px]"
                                    >
                                        {item.original_filename}
                                    </Link>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {item.type_label},{' '}
                                        {item.imported_count.toLocaleString()}{' '}
                                        of {item.total_rows.toLocaleString()}{' '}
                                        rows by {item.uploaded_by ?? 'unknown'},{' '}
                                        <RelativeTime iso={item.created_at} />
                                    </p>
                                </div>
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
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
