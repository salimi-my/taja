import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import WriterController from '@/actions/App/Http/Controllers/WriterController';
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
import type { Writer } from '@/types';

type WriterDeleteProps = {
    writer: Pick<Writer, 'id' | 'name'>;
    /** The button that opens the dialog. */
    children: ReactNode;
};

export function WriterDelete({ writer, children }: WriterDeleteProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(WriterController.destroy.url(writer.id), {
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
                    <AlertDialogTitle>Delete {writer.name}?</AlertDialogTitle>
                    <AlertDialogDescription>
                        The writer is removed from the lists. Its IPI name
                        number stays reserved, so it cannot be used for another
                        writer.
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
