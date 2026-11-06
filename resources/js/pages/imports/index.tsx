import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { ImportsTable } from '@/components/imports/imports-table';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/imports';
import type { CsvImportListItem, PaginatedResource } from '@/types';

type Props = {
    csvImports: PaginatedResource<CsvImportListItem>;
    filters: ServerFilters;
    types: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
};

export default function ImportsIndex(props: Props) {
    return (
        <>
            <Head title="Import history" />

            <ContentCard
                title="Import history"
                description="Every CSV file that was uploaded, with what came of it."
            >
                <ImportsTable {...props} />
            </ContentCard>
        </>
    );
}

ImportsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Import history',
            href: index(),
        },
    ],
};
