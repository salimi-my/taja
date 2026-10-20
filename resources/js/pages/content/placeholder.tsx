import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

type Props = {
    title: string;
    description: string;
};

/**
 * A dummy page used by the example Content menus. Replace it with real pages.
 */
export default function ContentPlaceholder({ title, description }: Props) {
    const { url } = usePage();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Content', href: url },
            { title, href: url },
        ],
    });

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <ContentCard title={title} description={description}>
                <Empty className="border-0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <FileText />
                        </EmptyMedia>
                        <EmptyTitle>Nothing here yet</EmptyTitle>
                        <EmptyDescription>
                            This is a dummy page for the {title} menu.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </ContentCard>
        </>
    );
}
