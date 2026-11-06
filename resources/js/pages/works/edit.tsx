import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WorkForm } from '@/components/works/work-form';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/works';
import type { WorkDetail, WorkEditOptions, WorkParty } from '@/types';

type WorksEditProps = WorkEditOptions & {
    work: { data: WorkDetail };
    parties: { data: WorkParty[] };
};

function WorksEditContent({
    work: { data: work },
    parties: { data: parties },
    ...options
}: WorksEditProps) {
    return (
        <>
            <Head title={`Edit ${work.title}`} />

            <ContentCard
                title="Edit Work"
                description={`Make changes to ${work.title} (${work.tap_number}). Click save when you're done.`}
            >
                <WorkForm
                    work={work}
                    parties={parties}
                    options={options}
                    cancelHref={show(work.id)}
                />
            </ContentCard>
        </>
    );
}

/** Keyed by record so the form state resets when moving between records. */
export default function WorksEdit(props: WorksEditProps) {
    return <WorksEditContent key={props.work.data.id} {...props} />;
}

WorksEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Works', href: index() },
        { title: 'Edit', href: index() },
    ],
};
