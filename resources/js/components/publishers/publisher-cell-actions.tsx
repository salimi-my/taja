import { Link } from '@inertiajs/react';
import { Eye, Ellipsis, Trash2 } from 'lucide-react';
import { PublisherDelete } from '@/components/publishers/publisher-delete';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { show } from '@/routes/publishers';
import type { Publisher } from '@/types';

type PublisherCellActionsProps = {
    publisher: Publisher;
};

export function PublisherCellActions({ publisher }: PublisherCellActionsProps) {
    const isAdmin = useIsAdmin();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    aria-label="Open menu"
                    variant="ghost"
                    className="flex size-8 p-0 data-[state=open]:bg-muted"
                >
                    <Ellipsis className="size-4" aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuItem asChild>
                    <Link
                        href={show(publisher.id)}
                        className="cursor-pointer text-sm font-medium"
                    >
                        <Eye className="mr-2 size-4" aria-hidden="true" />
                        View
                    </Link>
                </DropdownMenuItem>
                {isAdmin && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <PublisherDelete publisher={publisher}>
                                <Button
                                    variant="ghost"
                                    className="group w-full justify-start px-2"
                                >
                                    <Trash2
                                        className="mr-2 size-4 group-hover:text-destructive"
                                        aria-hidden="true"
                                    />
                                    <span className="group-hover:text-destructive">
                                        Delete
                                    </span>
                                </Button>
                            </PublisherDelete>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
