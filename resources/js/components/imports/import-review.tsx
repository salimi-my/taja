import { CircleX, Download, ListChecks, TriangleAlert } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ContentCard } from '@/components/content-card';
import { ImportIssuesTable } from '@/components/imports/import-issues-table';
import {
    ImportSampleTable,
    SAMPLE_MAX,
    SAMPLE_STEP,
} from '@/components/imports/import-sample-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { errors as errorsFile } from '@/routes/imports';
import type { CsvImport, ImportSample } from '@/types';

type ReviewTab = 'errors' | 'warnings' | 'sample';

type ImportReviewProps = {
    csvImport: CsvImport;
    sample: ImportSample | null;
};

function TabCount({ value }: { value: number }) {
    return (
        <Badge className="min-w-5 bg-foreground/10 px-1.5 text-foreground tabular-nums in-data-[state=active]:bg-primary-foreground in-data-[state=active]:text-primary">
            {value.toLocaleString()}
        </Badge>
    );
}

/** The errors, warnings and sample rows of an import, in one tabbed card. */
export function ImportReview({ csvImport, sample }: ImportReviewProps) {
    const hasErrors = csvImport.errors_total > 0;
    const hasWarnings = csvImport.warnings_total > 0;

    const availableTabs = useMemo(() => {
        const tabs: ReviewTab[] = [];

        if (hasErrors) {
            tabs.push('errors');
        }

        if (sample) {
            tabs.push('sample');
        }

        if (hasWarnings) {
            tabs.push('warnings');
        }

        return tabs;
    }, [hasErrors, hasWarnings, sample]);

    const [selectedTab, setSelectedTab] = useState<ReviewTab>(
        availableTabs[0] ?? 'sample',
    );
    const [highlight, setHighlight] = useState<{
        tab: ReviewTab;
        line: number;
    } | null>(null);
    const [loadingLine, setLoadingLine] = useState<number | null>(null);
    const activeTab = availableTabs.includes(selectedTab)
        ? selectedTab
        : availableTabs[0];

    const sampleLines = useMemo(
        () => new Set(sample?.rows.map((row) => row.line) ?? []),
        [sample],
    );

    useEffect(() => {
        if (!highlight || activeTab !== highlight.tab) {
            return;
        }

        const target = document.getElementById(
            `${highlight.tab === 'sample' ? 'sample-row' : 'issue-line'}-${highlight.line}`,
        );
        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const timer = window.setTimeout(() => setHighlight(null), 2500);

        return () => window.clearTimeout(timer);
    }, [highlight, activeTab]);

    const jumpTo = (tab: ReviewTab, line: number) => {
        setSelectedTab(tab);
        setHighlight({ tab, line });
    };

    /** Line 1 is the header, so line N is data row N - 1. */
    const lastPreviewLine = Math.min(SAMPLE_MAX, csvImport.total_rows) + 1;
    const canViewRow = (line: number) => line > 1 && line <= lastPreviewLine;

    const viewRow = (line: number) => {
        if (sampleLines.has(line)) {
            jumpTo('sample', line);

            return;
        }

        const rowsNeeded = Math.ceil((line - 1) / SAMPLE_STEP) * SAMPLE_STEP;

        router.reload({
            data: { sample_rows: Math.min(rowsNeeded, SAMPLE_MAX) },
            only: ['sample'],
            onStart: () => setLoadingLine(line),
            onSuccess: () => jumpTo('sample', line),
            onFinish: () => setLoadingLine(null),
        });
    };

    if (availableTabs.length === 0 || !activeTab) {
        return null;
    }

    return (
        <ContentCard
            title="Review the file"
            description="Rows with errors are skipped. Fix them in the file and upload it again."
        >
            <Tabs
                value={activeTab}
                onValueChange={(value) => setSelectedTab(value as ReviewTab)}
                className="gap-4"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <TabsList>
                        {hasErrors && (
                            <TabsTrigger value="errors" className="px-3">
                                <CircleX />
                                Errors
                                <TabCount value={csvImport.errors_total} />
                            </TabsTrigger>
                        )}
                        {sample && (
                            <TabsTrigger value="sample" className="px-3">
                                <ListChecks />
                                Sample rows
                            </TabsTrigger>
                        )}
                        {hasWarnings && (
                            <TabsTrigger value="warnings" className="px-3">
                                <TriangleAlert />
                                Warnings
                                <TabCount value={csvImport.warnings_total} />
                            </TabsTrigger>
                        )}
                    </TabsList>

                    {activeTab === 'errors' && csvImport.has_errors_file && (
                        <Button variant="outline" size="sm" asChild>
                            <a href={errorsFile.url(csvImport.id)}>
                                <Download />
                                Download errors CSV
                            </a>
                        </Button>
                    )}
                </div>

                {hasErrors && (
                    <TabsContent value="errors">
                        <ImportIssuesTable
                            tone="error"
                            issues={csvImport.errors}
                            total={csvImport.errors_total}
                            canViewRow={sample ? canViewRow : undefined}
                            loadingLine={loadingLine}
                            previewLimit={SAMPLE_MAX}
                            highlightLine={
                                highlight?.tab === 'errors'
                                    ? highlight.line
                                    : null
                            }
                            onViewRow={viewRow}
                        />
                    </TabsContent>
                )}

                {sample && (
                    <TabsContent value="sample">
                        <ImportSampleTable
                            sample={sample}
                            total={csvImport.total_rows}
                            highlightLine={
                                highlight?.tab === 'sample'
                                    ? highlight.line
                                    : null
                            }
                            onViewErrors={(line) => jumpTo('errors', line)}
                        />
                    </TabsContent>
                )}

                {hasWarnings && (
                    <TabsContent value="warnings">
                        <div className="flex flex-col gap-3">
                            <p className="text-sm text-muted-foreground">
                                Warnings do not stop a row from being imported.
                                Check that each one is expected.
                            </p>
                            <ImportIssuesTable
                                tone="warning"
                                issues={csvImport.warnings}
                                total={csvImport.warnings_total}
                            />
                        </div>
                    </TabsContent>
                )}
            </Tabs>
        </ContentCard>
    );
}
