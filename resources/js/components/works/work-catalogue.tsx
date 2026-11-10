import { router } from '@inertiajs/react';
import { FileSearch } from 'lucide-react';
import { ReportActions } from '@/components/reports/report-actions';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Label } from '@/components/ui/label';
import { WorkCatalogueTable } from '@/components/works/work-catalogue-table';
import type { WorkParty } from '@/types';

type WorkCatalogueProps = {
    rows: WorkParty[];
    includeEnded: boolean;
    /** The URL of the page that shows the catalogue, used by the toggle. */
    url: string;
    /** The props refreshed when the toggle changes, e.g. `['catalogue', 'includeEnded']`. */
    only: string[];
    /** The CSV export of the catalogue. When given, the Export CSV button is shown. */
    exportUrl?: string;
};

/**
 * The works a writer or publisher is a party of, with a toggle to include
 * involvement that has ended.
 */
export function WorkCatalogue({
    rows,
    includeEnded,
    url,
    only,
    exportUrl,
}: WorkCatalogueProps) {
    const toggleEnded = (checked: boolean) => {
        router.get(url, checked ? { include_ended: 1 } : {}, {
            only,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Checkbox
                        id="include-ended"
                        checked={includeEnded}
                        onCheckedChange={(checked) =>
                            toggleEnded(checked === true)
                        }
                    />
                    <Label
                        htmlFor="include-ended"
                        className="text-sm font-normal"
                    >
                        Include ended involvement
                    </Label>
                </div>
                {exportUrl && <ReportActions exportUrl={exportUrl} />}
            </div>

            {rows.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <FileSearch />
                        </EmptyMedia>
                        <EmptyTitle>No works yet</EmptyTitle>
                        <EmptyDescription>
                            Works appear here once this record is added as a
                            party of a work.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <WorkCatalogueTable rows={rows} />
            )}
        </div>
    );
}
