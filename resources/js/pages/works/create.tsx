import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WorkForm } from '@/components/works/work-form';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/works';
import type { WorkCreateOptions } from '@/types';

export default function WorksCreate(props: WorkCreateOptions) {
    return (
        <>
            <Head title="Create work" />

            <ContentCard
                title="Create Work"
                description="Register a new work. The TAP number is generated automatically."
            >
                <WorkForm options={props} cancelHref={index()} />
            </ContentCard>
        </>
    );
}

WorksCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Works', href: index() },
        { title: 'Create', href: create() },
    ],
};
