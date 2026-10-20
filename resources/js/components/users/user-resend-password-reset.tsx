import { router } from '@inertiajs/react';
import { MailPlus } from 'lucide-react';
import { useState } from 'react';
import UserPasswordResetLinkController from '@/actions/App/Http/Controllers/UserPasswordResetLinkController';
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

type UserResendPasswordResetProps = {
    user: ManagedUser;
};

export function UserResendPasswordReset({
    user,
}: UserResendPasswordResetProps) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const handleResend = () => {
        router.post(
            UserPasswordResetLinkController.url(user.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setLoading(true),
                onSuccess: () => setOpen(false),
                onFinish: () => setLoading(false),
            },
        );
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>
                <Button
                    variant="ghost"
                    className="group w-full justify-start px-2"
                >
                    <MailPlus className="mr-2 size-4" aria-hidden="true" />
                    <span>Reset Password</span>
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Resend Password Reset Link
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        This will send a password reset link to {user.email}.
                        Are you sure you want to continue?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        disabled={loading}
                        onClick={(event) => {
                            event.preventDefault();
                            handleResend();
                        }}
                    >
                        {loading ? (
                            <>
                                <Spinner className="size-4!" />
                                Sending...
                            </>
                        ) : (
                            'Send Reset Link'
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
