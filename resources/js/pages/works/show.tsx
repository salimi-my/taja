import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { WorkDelete } from '@/components/works/work-delete';
import { WorkDetails } from '@/components/works/work-details';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { edit, index, show } from '@/routes/works';
import type {
    PaginatedResource,
    RightsHistoryEntry,
    WorkDetail,
    WorkParty,
    WorkPartyOptions,
} from '@/types';

type Props = {
    work: { data: WorkDetail };
    parties: { data: WorkParty[] };
    asOf: string | null;
    partyOptions: WorkPartyOptions | null;
    history?: PaginatedResource<RightsHistoryEntry>;
};

export default function WorksShow({
    work: { data: work },
    parties: { data: parties },
    asOf,
    partyOptions,
    history,
}: Props) {
    const isAdmin = useIsAdmin();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Works', href: index() },
            { title: work.title, href: show(work.id) },
        ],
    });

    return (
        <>
            <Head title={work.title} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={index()}>
                        <ArrowLeft />
                        All works
                    </Link>
                </Button>

                {isAdmin && (
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={edit(work.id)}>
                                <Edit />
                                Edit
                            </Link>
                        </Button>
                        <WorkDelete work={work}>
                            <Button variant="outline" size="sm">
                                <Trash2 />
                                Delete
                            </Button>
                        </WorkDelete>
                    </div>
                )}
            </div>

            <WorkDetails
                work={work}
                parties={parties}
                asOf={asOf}
                partyOptions={partyOptions}
                history={history}
            />
        </>
    );
}
