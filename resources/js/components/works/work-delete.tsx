import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import WorkController from '@/actions/App/Http/Controllers/WorkController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';
import type { Work } from '@/types';

type WorkDeleteProps = {
    work: Pick<Work, 'id' | 'title' | 'tap_number'>;
    /** The button that opens the dialog. */
    children: ReactNode;
};

export function WorkDelete({ work, children }: WorkDeleteProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(WorkController.destroy.url(work.id), {
            preserveScroll: true,
            onStart: () => setLoading(true),
            onSuccess: () => setOpen(false),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Delete {work.title} ({work.tap_number})?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        The work is removed from the lists. Its TAP number and
                        MACP Work ID stay reserved, and its rights history is
                        kept.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        disabled={loading}
                        onClick={(event) => {
                            event.preventDefault();
                            handleDelete();
                        }}
                    >
                        {loading ? (
                            <>
                                <Spinner className="size-4" />
                                Deleting...
                            </>
                        ) : (
                            <>
                                <Trash2 />
                                Delete
                            </>
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
