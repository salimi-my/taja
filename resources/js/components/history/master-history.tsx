import { router } from '@inertiajs/react';
import { HistoryTimeline } from '@/components/history/history-timeline';
import type { MasterHistoryEntry, PaginatedResource } from '@/types';

type MasterHistoryProps = {
    /** The show page of the writer or publisher. */
    url: string;
    /** What the records are called in the empty state, e.g. `writer`. */
    subjectLabel: string;
    /** `undefined` while the deferred prop is loading. */
    history: PaginatedResource<MasterHistoryEntry> | undefined;
};

/** The History tab of a writer or publisher: every recorded change, newest first. */
export function MasterHistory({
    url,
    subjectLabel,
    history,
}: MasterHistoryProps) {
    return (
        <HistoryTimeline
            history={history}
            emptyDescription={`Changes to the ${subjectLabel} are listed here.`}
            onPageChange={(page) =>
                router.get(
                    url,
                    { history_page: page },
                    {
                        only: ['history'],
                        preserveState: true,
                        preserveScroll: true,
                    },
                )
            }
        />
    );
}
