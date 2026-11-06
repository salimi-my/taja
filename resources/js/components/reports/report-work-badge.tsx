import { X } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';
import { Badge } from '@/components/ui/badge';
import type { Params, ReportWorkFilter } from '@/types';

type ReportWorkBadgeProps = {
    work: ReportWorkFilter | null;
    setParams: Dispatch<SetStateAction<Params>>;
};

/** Shows the work a report is narrowed to, with a button to remove the filter. */
export function ReportWorkBadge({ work, setParams }: ReportWorkBadgeProps) {
    if (work === null) {
        return null;
    }

    return (
        <Badge variant="secondary" className="gap-1 print:hidden">
            Work: {work.tap_number} {work.title}
            <button
                type="button"
                aria-label="Show every work"
                className="rounded-sm hover:text-destructive"
                onClick={() =>
                    setParams((previous) => ({
                        ...previous,
                        page: 1,
                        work_id: undefined,
                    }))
                }
            >
                <X className="size-3" />
            </button>
        </Badge>
    );
}
