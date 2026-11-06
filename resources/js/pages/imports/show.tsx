import { Head, router, setLayoutProps } from '@inertiajs/react';
import { CircleAlert, CircleCheckBig, TriangleAlert } from 'lucide-react';
import { useEffect } from 'react';
import { ContentCard } from '@/components/content-card';
import { ImportActions } from '@/components/imports/import-actions';
import { ImportIssuesTable } from '@/components/imports/import-issues-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import { importListRoutes } from '@/lib/imports';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/imports';
import type { CsvImport } from '@/types';

type Props = {
    csvImport: { data: CsvImport };
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

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border p-4">
            <div className="text-2xl font-semibold tabular-nums">
                {value.toLocaleString()}
            </div>
            <div className="text-sm text-muted-foreground">{label}</div>
        </div>
    );
}

export default function ImportsShow({ csvImport: { data: csvImport } }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: csvImport.type_label,
                href: importListRoutes[csvImport.type].href,
            },
            { title: 'Import', href: create(csvImport.type) },
            { title: `#${csvImport.id}`, href: show(csvImport.id) },
        ],
    });

    const isPreview = csvImport.status === 'previewed';
    const isValidating = csvImport.status === 'validating';
    const isImporting = csvImport.status === 'importing';

    useEffect(() => {
        if (!csvImport.is_processing) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: ['csvImport'] });
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
                    description={`${csvImport.original_filename} · ${csvImport.status_label}${csvImport.uploaded_by ? ` · uploaded by ${csvImport.uploaded_by}` : ''}`}
                >
                    <div className="flex flex-col gap-6">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Stat
                                label="Rows in file"
                                value={csvImport.total_rows}
                            />
                            <Stat
                                label="Valid rows"
                                value={csvImport.valid_rows}
                            />
                            <Stat
                                label="Rows with errors"
                                value={csvImport.error_rows}
                            />
                            {(csvImport.status === 'imported' ||
                                isImporting) && (
                                <Stat
                                    label="Rows imported"
                                    value={csvImport.imported_count}
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

                        <ImportActions csvImport={csvImport} />
                    </div>
                </ContentCard>

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
