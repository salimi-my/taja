import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { WriterDelete } from '@/components/writers/writer-delete';
import { WriterDetails } from '@/components/writers/writer-details';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { edit, index, show } from '@/routes/writers';
import type { WorkParty, WriterDetail, WriterOptions } from '@/types';

type Props = Pick<WriterOptions, 'rightsTypes'> & {
    writer: { data: WriterDetail };
    catalogue: { data: WorkParty[] };
    includeEnded: boolean;
};

export default function WritersShow({
    writer: { data: writer },
    catalogue: { data: catalogue },
    includeEnded,
    rightsTypes,
}: Props) {
    const isAdmin = useIsAdmin();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Writers', href: index() },
            { title: writer.name, href: show(writer.id) },
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
            <Head title={writer.name} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={index()}>
                        <ArrowLeft />
                        All writers
                    </Link>
                </Button>

                {isAdmin && (
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={edit(writer.id)}>
                                <Edit />
                                Edit
                            </Link>
                        </Button>
                        <WriterDelete writer={writer}>
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
                        </WriterDelete>
                    </div>
                )}
            </div>

            <WriterDetails
                writer={writer}
                rightsTypeLabels={rightsTypeLabels}
                catalogue={catalogue}
                includeEnded={includeEnded}
            />
        </>
    );
}
