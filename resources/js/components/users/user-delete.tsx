import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
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
import type { ManagedUser } from '@/types';

type UserDeleteProps = {
    user: ManagedUser;
};

export function UserDelete({ user }: UserDeleteProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleDelete = () => {
        router.delete(UserController.destroy.url(user.id), {
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
                {user.is_current_user ? (
                    <>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                This is your account
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be done. You cannot delete
                                your own account.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Okay, got it</AlertDialogCancel>
                        </AlertDialogFooter>
                    </>
                ) : (
                    <>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Are you absolutely sure?
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be undone. This will
                                permanently delete {user.name} and remove their
                                data from our servers.
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
                                    'Continue'
                                )}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </>
                )}
            </AlertDialogContent>
        </AlertDialog>
    );
}
