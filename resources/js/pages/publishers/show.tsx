import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import { PublisherDelete } from '@/components/publishers/publisher-delete';
import { PublisherDetails } from '@/components/publishers/publisher-details';
import { Button } from '@/components/ui/button';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { edit, index, show } from '@/routes/publishers';
import type { PublisherDetail, PublisherOptions, WorkParty } from '@/types';

type Props = Pick<PublisherOptions, 'rightsTypes'> & {
    publisher: { data: PublisherDetail };
    catalogue: { data: WorkParty[] };
    includeEnded: boolean;
};

export default function PublishersShow({
    publisher: { data: publisher },
    catalogue: { data: catalogue },
    includeEnded,
    rightsTypes,
}: Props) {
    const isAdmin = useIsAdmin();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Publishers', href: index() },
            { title: publisher.name, href: show(publisher.id) },
        ],
    });

    const rightsTypeLabels = useMemo(
        () =>
            Object.fromEntries(
                rightsTypes.map((item) => [item.value, item.label]),
            ),
        [rightsTypes],
    );

    return (
        <>
            <Head title={publisher.name} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={index()}>
                        <ArrowLeft />
                        All publishers
                    </Link>
                </Button>

                {isAdmin && (
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={edit(publisher.id)}>
                                <Edit />
                                Edit
                            </Link>
                        </Button>
                        <PublisherDelete publisher={publisher}>
                            <Button
                                variant="outline"
                                size="sm"
                                className="group"
                            >
                                <Trash2 className="group-hover:text-destructive" />
                                <span className="group-hover:text-destructive">
                                    Delete
                                </span>
                            </Button>
                        </PublisherDelete>
                    </div>
                )}
            </div>

            <PublisherDetails
                publisher={publisher}
                rightsTypeLabels={rightsTypeLabels}
                catalogue={catalogue}
                includeEnded={includeEnded}
            />
        </>
    );
}
