import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { PublisherForm } from '@/components/publishers/publisher-form';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/publishers';
import type { PublisherOptions } from '@/types';

type PublishersCreateProps = PublisherOptions;

export default function PublishersCreate(props: PublishersCreateProps) {
    return (
        <>
            <Head title="Create publisher" />

            <ContentCard
                title="Create Publisher"
                description="Add a new publisher. The publisher code is generated automatically."
            >
                <PublisherForm options={props} cancelHref={index()} />
            </ContentCard>
        </>
    );
}

PublishersCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Publishers', href: index() },
        { title: 'Create', href: create() },
    ],
};
