import { Head } from '@inertiajs/react';
import { PublishersTable } from '@/components/publishers/publishers-table';
import { ContentCard } from '@/components/content-card';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/publishers';
import type { PaginatedResource, Publisher, PublisherOptions } from '@/types';

type Props = PublisherOptions & {
    publishers: PaginatedResource<Publisher>;
    filters: ServerFilters;
};

export default function PublishersIndex(props: Props) {
    return (
        <>
            <Head title="Publishers" />

            <ContentCard
                title="Publishers"
                description="The publisher clients whose catalogue TAJA administers."
            >
                <PublishersTable {...props} />
            </ContentCard>
        </>
    );
}

PublishersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Publishers',
            href: index(),
        },
    ],
};
