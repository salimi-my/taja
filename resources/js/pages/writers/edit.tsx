import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { WriterForm } from '@/components/writers/writer-form';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/writers';
import type { WriterDetail, WriterOptions } from '@/types';

type WritersEditProps = WriterOptions & {
    writer: { data: WriterDetail };
};

function WritersEditContent({
    writer: { data: writer },
    ...options
}: WritersEditProps) {
    return (
        <>
            <Head title={`Edit ${writer.name}`} />

            <ContentCard
                title="Edit Writer"
                description={`Make changes to ${writer.name}. Click save when you're done.`}
            >
                <WriterForm
                    writer={writer}
                    options={options}
                    cancelHref={show(writer.id)}
                />
            </ContentCard>
        </>
    );
}

/** Keyed by record so the form state resets when moving between records. */
export default function WritersEdit(props: WritersEditProps) {
    return <WritersEditContent key={props.writer.data.id} {...props} />;
}

WritersEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Writers', href: index() },
        { title: 'Edit', href: index() },
    ],
};
