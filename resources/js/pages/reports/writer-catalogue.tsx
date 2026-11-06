import { Head } from '@inertiajs/react';
import { PenLine, X } from 'lucide-react';
import { useMemo } from 'react';
import { ContentCard } from '@/components/content-card';
import { ReportActions } from '@/components/reports/report-actions';
import { TablePagination } from '@/components/table/table-pagination';
import { TableQueryState } from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Label } from '@/components/ui/label';
import { WorkCatalogueTable } from '@/components/works/work-catalogue-table';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery, reportDefaults } from '@/lib/reports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { writerCatalogue } from '@/routes/reports';
import { writerCatalogue as exportWriterCatalogue } from '@/routes/reports/export';
import type { PaginatedResource, ReportWriterOption, WorkParty } from '@/types';

type Props = {
    rows: PaginatedResource<WorkParty>;
    filters: ServerFilters;
    writer: ReportWriterOption | null;
    writers: ReportWriterOption[];
};

const DEFAULTS = reportDefaults('effective_start_date.desc');
const REFRESHED_PROPS = ['rows', 'filters', 'writer'];

export default function WriterCatalogueReport({
    rows,
    filters,
    writer,
    writers,
}: Props) {
    const { params, setParams, isLoading, hasError, retry } = useTableParams({
        url: writerCatalogue().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    const writerOptions = useMemo(
        () =>
            writers.map((item) => ({
                value: String(item.id),
                label: `${item.name} (${item.code})`,
            })),
        [writers],
    );

    return (
        <>
            <Head title="Writer Catalogue" />

            <ContentCard
                title="Writer Catalogue"
                description="Every work a writer is a party of, with the shares, territories and dates."
            >
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3 print:hidden">
                        <div className="flex items-center gap-1">
                            <div className="w-72">
                                <Combobox
                                    options={writerOptions}
                                    value={params.writer_id ?? null}
                                    onValueChange={(next) =>
                                        setParams((previous) => ({
                                            ...previous,
                                            page: 1,
                                            writer_id: String(next),
                                        }))
                                    }
                                    placeholder="Select a writer"
                                    searchPlaceholder="Search writers..."
                                />
                            </div>
                            {params.writer_id && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Clear writer"
                                    onClick={() =>
                                        setParams((previous) => ({
                                            ...previous,
                                            page: 1,
                                            writer_id: undefined,
                                        }))
                                    }
                                >
                                    <X />
                                </Button>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="include-ended"
                                checked={params.include_ended === '1'}
                                onCheckedChange={(checked) =>
                                    setParams((previous) => ({
                                        ...previous,
                                        page: 1,
                                        include_ended:
                                            checked === true ? '1' : undefined,
                                    }))
                                }
                            />
                            <Label
                                htmlFor="include-ended"
                                className="text-sm font-normal"
                            >
                                Include ended involvement
                            </Label>
                        </div>

                        <div className="ml-auto">
                            <ReportActions
                                disabled={writer === null}
                                exportUrl={
                                    exportWriterCatalogue({
                                        query: exportQuery(params, DEFAULTS),
                                    }).url
                                }
                            />
                        </div>
                    </div>

                    {writer && (
                        <p className="text-sm text-muted-foreground">
                            Writer:{' '}
                            <span className="font-medium text-foreground">
                                {writer.name}
                            </span>{' '}
                            <span className="font-mono text-xs">
                                {writer.code}
                            </span>
                            {params.include_ended === '1'
                                ? ' (including ended involvement)'
                                : ' (current involvement)'}
                        </p>
                    )}

                    {writer === null ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <PenLine />
                                </EmptyMedia>
                                <EmptyTitle>Choose a writer</EmptyTitle>
                                <EmptyDescription>
                                    Pick a writer above to see every work they
                                    are a party of.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <TableQueryState
                            isLoading={isLoading}
                            hasError={hasError}
                            data={rows}
                            resourceName="the catalogue"
                            onRetry={retry}
                            skeleton={<TableSkeleton columnCount={12} />}
                        >
                            {(data) =>
                                data.data.length === 0 ? (
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyTitle>No works</EmptyTitle>
                                            <EmptyDescription>
                                                This writer is not a party of
                                                any work yet.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                ) : (
                                    <>
                                        <WorkCatalogueTable rows={data.data} />
                                        <div className="print:hidden">
                                            <TablePagination
                                                total={data.meta.total}
                                                params={params}
                                                setParams={setParams}
                                            />
                                        </div>
                                    </>
                                )
                            }
                        </TableQueryState>
                    )}
                </div>
            </ContentCard>
        </>
    );
}

WriterCatalogueReport.layout = {
    breadcrumbs: [
        { title: 'Reports', href: writerCatalogue() },
        { title: 'Writer Catalogue', href: writerCatalogue() },
    ],
};
