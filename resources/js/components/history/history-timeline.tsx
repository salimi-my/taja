import { ChevronDown, History } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { ImportSourceLink } from '@/components/imports/import-source-link';
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
import { UserAvatarName } from '@/components/user-avatar-name';
import { changeTypeStyle } from '@/components/works/change-type-style';
import {
    RightsSnapshotDiff,
    snapshotDiffRows,
} from '@/components/works/rights-snapshot-diff';
import { cn } from '@/lib/utils';
import type { PaginatedResource, RightsSnapshot } from '@/types';

/** The fields of a history entry the timeline shows, shared by work and master entries. */
export type HistoryTimelineEntry = {
    id: number;
    change_type: string;
    change_type_label: string;
    summary: string;
    /** Only work entries have an effective date. */
    effective_date?: string;
    recorded_at: string;
    user_name: string | null;
    user_avatar_url: string | null;
    csv_import_id: number | null;
    remarks: string | null;
    previous_snapshot: RightsSnapshot | null;
    new_snapshot: RightsSnapshot | null;
};

type HistoryTimelineProps = {
    /** `undefined` while the deferred prop is loading. */
    history: PaginatedResource<HistoryTimelineEntry> | undefined;
    emptyDescription: string;
    onPageChange: (page: number) => void;
};

function HistorySkeleton() {
    return (
        <div
            className="space-y-6"
            aria-busy="true"
            aria-label="Loading history"
        >
            {[0, 1, 2].map((item) => (
                <div key={item} className="flex gap-4">
                    <Skeleton className="size-8 shrink-0 rounded-full" />
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-4 w-1/3" />
                        <Skeleton className="h-4 w-3/4" />
                    </div>
                </div>
            ))}
        </div>
    );
}

function HistoryEntry({ entry }: { entry: HistoryTimelineEntry }) {
    const { icon: Icon, classes } = changeTypeStyle(entry.change_type);
    const changeCount = snapshotDiffRows(
        entry.previous_snapshot,
        entry.new_snapshot,
    ).length;
    const hasSnapshots =
        entry.previous_snapshot !== null || entry.new_snapshot !== null;

    return (
        <li className="group/entry relative pb-7 pl-12 last:pb-0">
            <span
                aria-hidden="true"
                className="absolute top-10 bottom-1 left-4 w-px -translate-x-1/2 bg-border group-last/entry:hidden"
            />
            <span
                className={cn(
                    'absolute top-0 left-0 flex size-8 items-center justify-center rounded-full ring-4 ring-card',
                    classes,
                )}
            >
                <Icon aria-hidden="true" className="size-4" />
            </span>
            <div className="min-w-0 space-y-2">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span className="text-sm font-semibold">
                        {entry.change_type_label}
                    </span>
                    {entry.effective_date && (
                        <span className="text-xs text-muted-foreground">
                            Effective {formatDate(entry.effective_date)}
                        </span>
                    )}
                </div>
                <p className="text-sm">{entry.summary}</p>
                {entry.remarks && (
                    <p className="border-l-2 pl-3 text-sm text-muted-foreground italic">
                        {entry.remarks}
                    </p>
                )}
                <p className="flex flex-wrap items-center gap-x-1 text-xs text-muted-foreground">
                    <span>Recorded {formatDate(entry.recorded_at)} by</span>
                    {entry.user_name ? (
                        <UserAvatarName
                            name={entry.user_name}
                            avatarUrl={entry.user_avatar_url}
                            size="compact"
                        />
                    ) : entry.csv_import_id ? (
                        'an import'
                    ) : (
                        'the system'
                    )}
                    <ImportSourceLink
                        csvImportId={entry.csv_import_id}
                        className="ml-2"
                    />
                </p>
                {hasSnapshots && (
                    <Collapsible>
                        <CollapsibleTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="group/trigger"
                            >
                                <ChevronDown className="transition-transform group-data-[state=open]/trigger:rotate-180" />
                                Show changes ({changeCount})
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent className="pt-2">
                            <RightsSnapshotDiff
                                previous={entry.previous_snapshot}
                                next={entry.new_snapshot}
                            />
                        </CollapsibleContent>
                    </Collapsible>
                )}
            </div>
        </li>
    );
}

/** A timeline of recorded changes, newest first, with a skeleton, an empty state and paging. */
export function HistoryTimeline({
    history,
    emptyDescription,
    onPageChange,
}: HistoryTimelineProps) {
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
                    <EmptyDescription>{emptyDescription}</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    const { current_page: page, last_page: lastPage } = history.meta;

    return (
        <div className="space-y-4">
            <ol>
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
                            onClick={() => onPageChange(page - 1)}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page >= lastPage}
                            onClick={() => onPageChange(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
