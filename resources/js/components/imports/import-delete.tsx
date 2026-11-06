import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { destroy } from '@/routes/imports';
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
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { CsvImportListItem } from '@/types';

type ImportDeleteProps = {
    csvImport: Pick<CsvImportListItem, 'id' | 'original_filename'>;
};

/** The Delete item of the import row menu, with its confirmation dialog. */
export function ImportDelete({ csvImport }: ImportDeleteProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(destroy.url(csvImport.id), {
            preserveScroll: true,
            onStart: () => setLoading(true),
            onSuccess: () => setOpen(false),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>
                <Button
                    variant="ghost"
                    className="group w-full justify-start px-2"
                >
                    <Trash2
                        className="mr-2 size-4 group-hover:text-destructive"
                        aria-hidden="true"
                    />
                    <span className="group-hover:text-destructive">Delete</span>
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Delete {csvImport.original_filename}?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        The import is removed from the history and its uploaded
                        file is deleted. Records it already created are kept.
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
