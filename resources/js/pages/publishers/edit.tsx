import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { PublisherForm } from '@/components/publishers/publisher-form';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/publishers';
import type { PublisherDetail, PublisherOptions } from '@/types';

type PublishersEditProps = PublisherOptions & {
    publisher: { data: PublisherDetail };
};

function PublishersEditContent({
    publisher: { data: publisher },
    ...options
}: PublishersEditProps) {
    return (
        <>
            <Head title={`Edit ${publisher.name}`} />

            <ContentCard
                title="Edit Publisher"
                description={`Make changes to ${publisher.name}. Click save when you're done.`}
            >
                <PublisherForm
                    publisher={publisher}
                    options={options}
                    cancelHref={show(publisher.id)}
                />
            </ContentCard>
        </>
    );
}

/** Keyed by record so the form state resets when moving between records. */
export default function PublishersEdit(props: PublishersEditProps) {
    return <PublishersEditContent key={props.publisher.data.id} {...props} />;
}

PublishersEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Publishers', href: index() },
        { title: 'Edit', href: index() },
    ],
};
