import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WritersTable } from '@/components/writers/writers-table';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/writers';
import type { PaginatedResource, Writer, WriterOptions } from '@/types';

type Props = WriterOptions & {
    writers: PaginatedResource<Writer>;
    filters: ServerFilters;
};

export default function WritersIndex(props: Props) {
    return (
        <>
            <Head title="Writers" />

            <ContentCard
                title="Writers"
                description="The writers and interested parties whose rights TAJA administers."
            >
                <WritersTable {...props} />
            </ContentCard>
        </>
    );
}

WritersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Writers',
            href: index(),
        },
    ],
};
