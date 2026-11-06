import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import PublisherController from '@/actions/App/Http/Controllers/PublisherController';
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
import type { Publisher } from '@/types';

type PublisherDeleteProps = {
    publisher: Pick<Publisher, 'id' | 'name'>;
    /** The button that opens the dialog. */
    children: ReactNode;
};

export function PublisherDelete({ publisher, children }: PublisherDeleteProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(PublisherController.destroy.url(publisher.id), {
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
                        Delete {publisher.name}?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        The publisher is removed from the lists. Its company
                        registration and IPI numbers stay reserved, so they
                        cannot be used for another publisher.
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
