import { Head, router, setLayoutProps } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    CircleAlert,
    CircleCheckBig,
    FileSpreadsheet,
    ListChecks,
    Rows3,
    TriangleAlert,
    Upload,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect } from 'react';
import { ContentCard } from '@/components/content-card';
import { ImportStatusBadge } from '@/components/imports/import-status-badge';
import { ImportActions } from '@/components/imports/import-actions';
import { ImportSampleTable } from '@/components/imports/import-sample-table';
import { ImportIssuesTable } from '@/components/imports/import-issues-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { importListRoutes } from '@/lib/imports';
import { dashboard } from '@/routes';
import { create } from '@/routes/imports';
import type { CsvImport, ImportSample } from '@/types';

type Props = {
    csvImport: { data: CsvImport };
    /** The first rows of the file; only sent while the import is previewed. */
    sample: ImportSample | null;
};

const POLL_INTERVAL_MS = 2000;

function ProgressBar({ percentage }: { percentage: number }) {
    return (
        <div
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={percentage}
            className="h-2 w-full overflow-hidden rounded-full bg-muted"
        >
            <div
                className="h-full rounded-full bg-primary transition-all duration-500"
                style={{ width: `${percentage}%` }}
            />
        </div>
    );
}

type StatTone = 'neutral' | 'success' | 'danger';

const statTones: Record<StatTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    danger: 'bg-destructive/10 text-destructive',
};

function Stat({
    label,
    value,
    icon: Icon,
    tone = 'neutral',
}: {
    label: string;
    value: number;
    icon: LucideIcon;
    tone?: StatTone;
}) {
    return (
        <div className="flex items-center gap-3 rounded-lg border p-4">
            <div
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-md',
                    statTones[tone],
                )}
            >
                <Icon className="size-5" />
            </div>
            <div className="min-w-0">
                <div className="text-2xl leading-none font-semibold tabular-nums">
                    {value.toLocaleString()}
                </div>
                <div className="mt-1 text-sm text-muted-foreground">
                    {label}
                </div>
            </div>
        </div>
    );
}

export default function ImportsShow({
    csvImport: { data: csvImport },
    sample,
}: Props) {
    const isPreview = csvImport.status === 'previewed';
    const isValidating = csvImport.status === 'validating';
    const isImporting = csvImport.status === 'importing';
    const showsImportedCount = csvImport.status === 'imported' || isImporting;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: csvImport.type_label,
                href: importListRoutes[csvImport.type].href,
            },
            {
                title:
                    isPreview || isValidating
                        ? 'Import preview'
                        : 'Import result',
                href: create(csvImport.type),
            },
        ],
    });

    useEffect(() => {
        if (!csvImport.is_processing) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: ['csvImport', 'sample'] });
        }, POLL_INTERVAL_MS);

        return () => window.clearInterval(timer);
    }, [csvImport.is_processing]);

    return (
        <>
            <Head title={`Import ${csvImport.type_label}`} />

            <div className="flex flex-col gap-6">
                <ContentCard
                    title={
                        isPreview || isValidating
                            ? 'Import preview'
                            : 'Import result'
                    }
                    description={`Importing ${csvImport.type_label.toLowerCase()} from a CSV file.`}
                >
                    <div className="flex flex-col gap-6">
                        <div className="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-background text-muted-foreground ring-1 ring-border">
                                <FileSpreadsheet className="size-5" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="truncate text-sm font-medium">
                                    {csvImport.original_filename}
                                </div>
                                <div className="truncate text-xs text-muted-foreground">
                                    {[
                                        csvImport.uploaded_by &&
                                            `Uploaded by ${csvImport.uploaded_by}`,
                                        csvImport.created_at &&
                                            format(
                                                new Date(csvImport.created_at),
                                                'dd MMM yyyy, HH:mm',
                                            ),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </div>
                            </div>
                            <ImportStatusBadge csvImport={csvImport} />
                        </div>

                        <div
                            className={cn(
                                'grid gap-3 sm:grid-cols-2',
                                showsImportedCount
                                    ? 'lg:grid-cols-4'
                                    : 'lg:grid-cols-3',
                            )}
                        >
                            <Stat
                                label="Rows in file"
                                value={csvImport.total_rows}
                                icon={Rows3}
                            />
                            <Stat
                                label="Valid rows"
                                value={csvImport.valid_rows}
                                icon={ListChecks}
                                tone={
                                    csvImport.valid_rows > 0
                                        ? 'success'
                                        : 'neutral'
                                }
                            />
                            <Stat
                                label="Rows with errors"
                                value={csvImport.error_rows}
                                icon={TriangleAlert}
                                tone={
                                    csvImport.error_rows > 0
                                        ? 'danger'
                                        : 'neutral'
                                }
                            />
                            {showsImportedCount && (
                                <Stat
                                    label="Rows imported"
                                    value={csvImport.imported_count}
                                    icon={Upload}
                                    tone="success"
                                />
                            )}
                        </div>

                        {csvImport.is_processing && (
                            <div className="flex flex-col gap-3 rounded-lg border p-4">
                                <div className="flex items-center gap-2 text-sm font-medium">
                                    <Spinner className="size-4" />
                                    {isValidating
                                        ? 'Checking the file'
                                        : 'Importing the valid rows'}
                                    <span className="ml-auto tabular-nums">
                                        {csvImport.progress}%
                                    </span>
                                </div>
                                <ProgressBar percentage={csvImport.progress} />
                                <p className="text-sm text-muted-foreground">
                                    {isValidating
                                        ? 'Nothing is imported yet. You can leave this page, the check keeps running.'
                                        : 'You can leave this page, the import keeps running. Do not upload the same file again.'}
                                </p>
                            </div>
                        )}

                        {csvImport.status === 'failed' && (
                            <Alert variant="destructive">
                                <CircleAlert />
                                <AlertTitle>The import failed</AlertTitle>
                                <AlertDescription>
                                    {csvImport.failure_message ??
                                        'Something went wrong while processing the file.'}
                                </AlertDescription>
                            </Alert>
                        )}

                        {isPreview && csvImport.layout_error && (
                            <Alert variant="destructive">
                                <CircleAlert />
                                <AlertTitle>
                                    This file cannot be imported as it is
                                </AlertTitle>
                                <AlertDescription>
                                    {csvImport.layout_error}
                                </AlertDescription>
                            </Alert>
                        )}

                        {isPreview && !csvImport.layout_error && (
                            <Alert>
                                <CircleAlert />
                                <AlertTitle>
                                    Nothing has been imported yet
                                </AlertTitle>
                                <AlertDescription>
                                    Only the valid rows are imported. Rows with
                                    errors are skipped.
                                </AlertDescription>
                            </Alert>
                        )}

                        {csvImport.status === 'imported' && (
                            <Alert variant="success">
                                <CircleCheckBig />
                                <AlertTitle>Import finished</AlertTitle>
                                <AlertDescription>
                                    {csvImport.imported_count.toLocaleString()}{' '}
                                    {csvImport.imported_count === 1
                                        ? 'row was'
                                        : 'rows were'}{' '}
                                    imported.
                                </AlertDescription>
                            </Alert>
                        )}

                        {csvImport.status === 'cancelled' && (
                            <Alert>
                                <CircleAlert />
                                <AlertTitle>Import cancelled</AlertTitle>
                                <AlertDescription>
                                    Nothing was imported.
                                </AlertDescription>
                            </Alert>
                        )}

                        <Separator />

                        <ImportActions csvImport={csvImport} />
                    </div>
                </ContentCard>

                {sample && (
                    <ContentCard
                        title="Sample rows"
                        description="The first rows of the file, as they will be read. Check that each value sits in the right column."
                    >
                        <ImportSampleTable
                            sample={sample}
                            total={csvImport.total_rows}
                        />
                    </ContentCard>
                )}

                {csvImport.errors_total > 0 && (
                    <ContentCard
                        title="Errors"
                        description="These rows are skipped. Fix them in the file and upload it again."
                    >
                        <ImportIssuesTable
                            issues={csvImport.errors}
                            total={csvImport.errors_total}
                        />
                    </ContentCard>
                )}

                {csvImport.warnings_total > 0 && (
                    <ContentCard
                        title="Warnings"
                        description="These do not stop a row from being imported."
                    >
                        <div className="flex flex-col gap-3">
                            <Alert>
                                <TriangleAlert />
                                <AlertDescription>
                                    Check that each warning is expected.
                                </AlertDescription>
                            </Alert>
                            <ImportIssuesTable
                                issues={csvImport.warnings}
                                total={csvImport.warnings_total}
                            />
                        </div>
                    </ContentCard>
                )}
            </div>
        </>
    );
}
