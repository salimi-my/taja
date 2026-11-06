import { router } from '@inertiajs/react';
import { ChevronDown, History } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Skeleton } from '@/components/ui/skeleton';
import { show } from '@/routes/works';
import type {
    PaginatedResource,
    RightsHistoryEntry,
    RightsSnapshot,
} from '@/types';

type WorkHistoryProps = {
    workId: number;
    asOf: string | null;
    /** `undefined` while the deferred prop is loading. */
    history: PaginatedResource<RightsHistoryEntry> | undefined;
};

function HistorySkeleton() {
    return (
        <div
            className="space-y-3"
            aria-busy="true"
            aria-label="Loading history"
        >
            {[0, 1, 2].map((item) => (
                <div key={item} className="space-y-2 rounded-lg border p-4">
                    <Skeleton className="h-4 w-1/3" />
                    <Skeleton className="h-4 w-3/4" />
                </div>
            ))}
        </div>
    );
}

function SnapshotBlock({
    title,
    snapshot,
}: {
    title: string;
    snapshot: RightsSnapshot | null;
}) {
    return (
        <div className="min-w-0 space-y-1">
            <p className="text-xs font-medium text-muted-foreground">{title}</p>
            <pre className="max-h-64 overflow-auto rounded-md bg-muted p-3 text-xs">
                {snapshot === null ? 'None' : JSON.stringify(snapshot, null, 2)}
            </pre>
        </div>
    );
}

function HistoryEntry({ entry }: { entry: RightsHistoryEntry }) {
    const hasSnapshots =
        entry.previous_snapshot !== null || entry.new_snapshot !== null;

    return (
        <li className="space-y-2 rounded-lg border p-4">
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant="secondary">{entry.change_type_label}</Badge>
                <span className="text-sm font-medium">
                    Effective {formatDate(entry.effective_date)}
                </span>
                <span className="text-xs text-muted-foreground">
                    Recorded {formatDate(entry.recorded_at)} by{' '}
                    {entry.user_name ??
                        (entry.csv_import_id ? 'an import' : 'the system')}
                </span>
            </div>
            <p className="text-sm">{entry.summary}</p>
            {entry.remarks && (
                <p className="text-sm text-muted-foreground">
                    Remarks: {entry.remarks}
                </p>
            )}
            {hasSnapshots && (
                <Collapsible>
                    <CollapsibleTrigger asChild>
                        <Button variant="ghost" size="sm" className="-ml-2">
                            <ChevronDown />
                            Previous and new state
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="mt-2 grid gap-3 sm:grid-cols-2">
                        <SnapshotBlock
                            title="Previous"
                            snapshot={entry.previous_snapshot}
                        />
                        <SnapshotBlock
                            title="New"
                            snapshot={entry.new_snapshot}
                        />
                    </CollapsibleContent>
                </Collapsible>
            )}
        </li>
    );
}

/** The History tab: every recorded change of the work, newest first. */
export function WorkHistory({ workId, asOf, history }: WorkHistoryProps) {
    if (history === undefined) {
        return <HistorySkeleton />;
    }

    if (history.data.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <History />
                    </EmptyMedia>
                    <EmptyTitle>No history yet</EmptyTitle>
                    <EmptyDescription>
                        Changes to the work and its parties are listed here.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    const { current_page: page, last_page: lastPage } = history.meta;

    const goToPage = (next: number) =>
        router.get(
            show.url(workId),
            { ...(asOf === null ? {} : { as_of: asOf }), history_page: next },
            {
                only: ['history'],
                preserveState: true,
                preserveScroll: true,
            },
        );

    return (
        <div className="space-y-4">
            <ol className="space-y-3">
                {history.data.map((entry) => (
                    <HistoryEntry key={entry.id} entry={entry} />
                ))}
            </ol>

            {lastPage > 1 && (
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        Page {page} of {lastPage}
                    </p>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page <= 1}
                            onClick={() => goToPage(page - 1)}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page >= lastPage}
                            onClick={() => goToPage(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
