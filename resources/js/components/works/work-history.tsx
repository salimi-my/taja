import { router } from '@inertiajs/react';
import { HistoryTimeline } from '@/components/history/history-timeline';
import { show } from '@/routes/works';
import type { PaginatedResource, RightsHistoryEntry } from '@/types';

type WorkHistoryProps = {
    workId: number;
    asOf: string | null;
    /** `undefined` while the deferred prop is loading. */
    history: PaginatedResource<RightsHistoryEntry> | undefined;
};

/** The History tab: every recorded change of the work, newest first. */
export function WorkHistory({ workId, asOf, history }: WorkHistoryProps) {
    return (
        <HistoryTimeline
            history={history}
            emptyDescription="Changes to the work and its parties are listed here."
            onPageChange={(page) =>
                router.get(
                    show.url(workId),
                    {
                        ...(asOf === null ? {} : { as_of: asOf }),
                        history_page: page,
                    },
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
