import { Head, setLayoutProps } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { ImportFieldGuide } from '@/components/imports/import-field-guide';
import { ImportOrderNote } from '@/components/imports/import-order-note';
import { ImportUploadForm } from '@/components/imports/import-upload-form';
import { importListRoutes } from '@/lib/imports';
import { dashboard } from '@/routes';
import { create } from '@/routes/imports';
import type { ImportColumnGuide, ImportType } from '@/types';

type Props = {
    type: ImportType;
    typeLabel: string;
    columns: ImportColumnGuide[];
    maxRows: number;
    maxFileMegabytes: number;
};

export default function ImportsCreate({
    type,
    typeLabel,
    columns,
    maxRows,
    maxFileMegabytes,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: typeLabel, href: importListRoutes[type].href },
            { title: 'Import', href: create(type) },
        ],
    });

    return (
        <>
            <Head title={`Import ${typeLabel}`} />

            <div className="flex flex-col gap-6">
                <ContentCard
                    title={`Import ${typeLabel}`}
                    description="Upload a CSV file. You will see a preview with any errors before anything is saved."
                >
                    <div className="flex flex-col gap-6">
                        <ImportOrderNote current={type} />
                        {type === 'works' && (
                            <p className="text-sm text-muted-foreground">
                                Use one row per contributor. All rows of a work
                                must follow each other, so sort the file by
                                Work_ID. Leave TAP_Number blank, the system
                                generates it. A work with an error in any row is
                                skipped whole.
                            </p>
                        )}
                        <ImportUploadForm
                            type={type}
                            maxRows={maxRows}
                            maxFileMegabytes={maxFileMegabytes}
                        />
                    </div>
                </ContentCard>

                <ContentCard
                    title="Field guide"
                    description="The columns of the file. Download the template to get the header row."
                >
                    <ImportFieldGuide columns={columns} />
                </ContentCard>
            </div>
        </>
    );
}
