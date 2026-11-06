import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WriterForm } from '@/components/writers/writer-form';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/writers';
import type { WriterOptions } from '@/types';

type WritersCreateProps = WriterOptions;

export default function WritersCreate(props: WritersCreateProps) {
    return (
        <>
            <Head title="Create writer" />

            <ContentCard
                title="Create Writer"
                description="Add a new writer. The writer code is generated automatically."
            >
                <WriterForm options={props} cancelHref={index()} />
            </ContentCard>
        </>
    );
}

WritersCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Writers', href: index() },
        { title: 'Create', href: create() },
    ],
};
