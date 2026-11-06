import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WorksTable } from '@/components/works/works-table';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/works';
import type { PaginatedResource, Work, WorkFilterOptions } from '@/types';

type Props = WorkFilterOptions & {
    works: PaginatedResource<Work>;
    filters: ServerFilters;
};

export default function WorksIndex(props: Props) {
    return (
        <>
            <Head title="Works" />

            <ContentCard
                title="Works"
                description="The song catalogue whose rights TAJA administers."
            >
                <WorksTable {...props} />
            </ContentCard>
        </>
    );
}

WorksIndex.layout = {
    breadcrumbs: [
        {
            title: 'Works',
            href: index(),
        },
    ],
};
