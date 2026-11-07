import { Head, router, setLayoutProps } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    CircleAlert,
    CircleCheckBig,
    CircleX,
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
import { ImportActionBar } from '@/components/imports/import-action-bar';
import { ImportReview } from '@/components/imports/import-review';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import { UserAvatarName } from '@/components/user-avatar-name';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/imports';
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

function OutcomeBar({
    valid,
    errors,
    total,
}: {
    valid: number;
    errors: number;
    total: number;
}) {
    if (total === 0) {
        return null;
    }

    return (
        <div
            role="img"
            aria-label={`${valid} valid rows and ${errors} rows with errors out of ${total}`}
            className="flex h-2 w-full gap-0.5 overflow-hidden rounded-full bg-muted"
        >
            {valid > 0 && (
                <div
                    className="h-full basis-0 bg-emerald-500 transition-all duration-500"
                    style={{ flexGrow: valid }}
                />
            )}
            {errors > 0 && (
                <div
                    className="h-full basis-0 bg-destructive transition-all duration-500"
                    style={{ flexGrow: errors }}
                />
            )}
        </div>
    );
}

type StatTone = 'neutral' | 'success' | 'warning' | 'danger';

const statTones: Record<StatTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
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
        <div className="flex items-center gap-3 rounded-lg border p-3">
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
            { title: 'Import history', href: index() },
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
                                <div className="flex min-w-0 items-center gap-1.5 text-xs whitespace-nowrap text-muted-foreground">
                                    {csvImport.uploaded_by && (
                                        <>
                                            <span className="shrink-0">
                                                Uploaded by
                                            </span>
                                            <UserAvatarName
                                                name={csvImport.uploaded_by}
                                                avatarUrl={
                                                    csvImport.uploaded_by_avatar_url
                                                }
                                                size="compact"
                                            />
                                        </>
                                    )}
                                    {csvImport.uploaded_by &&
                                        csvImport.created_at && (
                                            <span className="shrink-0">·</span>
                                        )}
                                    {csvImport.created_at && (
                                        <span className="shrink-0">
                                            {format(
                                                new Date(csvImport.created_at),
                                                'dd MMM yyyy, HH:mm',
                                            )}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <ImportStatusBadge csvImport={csvImport} />
                        </div>

                        <div className="flex flex-col gap-3">
                            <div className="grid grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] gap-3">
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
                                    icon={CircleX}
                                    tone={
                                        csvImport.error_rows > 0
                                            ? 'danger'
                                            : 'neutral'
                                    }
                                />
                                <Stat
                                    label="Warnings"
                                    value={csvImport.warnings_total}
                                    icon={TriangleAlert}
                                    tone={
                                        csvImport.warnings_total > 0
                                            ? 'warning'
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
                            {!csvImport.is_processing && (
                                <OutcomeBar
                                    valid={csvImport.valid_rows}
                                    errors={csvImport.error_rows}
                                    total={csvImport.total_rows}
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

                        {isPreview &&
                            !csvImport.layout_error &&
                            csvImport.valid_rows === 0 && (
                                <Alert variant="destructive">
                                    <CircleX />
                                    <AlertTitle>
                                        No rows can be imported
                                    </AlertTitle>
                                    <AlertDescription>
                                        Every row has an error. Fix them in the
                                        file and upload it again.
                                    </AlertDescription>
                                </Alert>
                            )}

                        {isPreview &&
                            !csvImport.layout_error &&
                            csvImport.valid_rows > 0 &&
                            csvImport.error_rows > 0 && (
                                <Alert className="border-amber-500/50 text-amber-600 *:data-[slot=alert-description]:text-amber-600/90 dark:text-amber-400 dark:*:data-[slot=alert-description]:text-amber-400/90">
                                    <TriangleAlert />
                                    <AlertTitle>
                                        {csvImport.error_rows.toLocaleString()}{' '}
                                        of{' '}
                                        {csvImport.total_rows.toLocaleString()}{' '}
                                        {csvImport.total_rows === 1
                                            ? 'row'
                                            : 'rows'}{' '}
                                        will be skipped
                                    </AlertTitle>
                                    <AlertDescription>
                                        Only the{' '}
                                        {csvImport.valid_rows.toLocaleString()}{' '}
                                        valid{' '}
                                        {csvImport.valid_rows === 1
                                            ? 'row is'
                                            : 'rows are'}{' '}
                                        imported. Nothing has been imported yet.
                                    </AlertDescription>
                                </Alert>
                            )}

                        {isPreview &&
                            !csvImport.layout_error &&
                            csvImport.valid_rows > 0 &&
                            csvImport.error_rows === 0 && (
                                <Alert variant="success">
                                    <CircleCheckBig />
                                    <AlertTitle>All rows are valid</AlertTitle>
                                    <AlertDescription>
                                        Nothing has been imported yet. Confirm
                                        to import them.
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
                    </div>
                </ContentCard>

                <ImportReview csvImport={csvImport} sample={sample} />

                <ImportActionBar csvImport={csvImport} />
            </div>
        </>
    );
}
