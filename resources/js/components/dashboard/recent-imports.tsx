import { Link } from '@inertiajs/react';
import { FileUp } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
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
import { show } from '@/routes/imports';
import type { RecentImport } from '@/types';

type RecentImportsProps = {
    imports: RecentImport[];
};

/** The latest CSV imports, each linking to its preview or result. Admins only. */
export function RecentImports({ imports }: RecentImportsProps) {
    return (
        <Card className="border-sidebar-border max-md:py-4">
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
                                className="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                            >
                                <div className="min-w-0">
                                    <Link
                                        href={show(item.id)}
                                        className="link-hover block truncate text-sm font-semibold after:h-[2px]"
                                    >
                                        {item.original_filename}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {item.type_label}, {item.imported_count}{' '}
                                        of {item.total_rows} rows imported by{' '}
                                        {item.uploaded_by ?? 'unknown'} on{' '}
                                        {formatDate(item.created_at)}
                                    </p>
                                </div>
                                <Badge variant="outline">
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
