import { router } from '@inertiajs/react';
import { Edit, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import ProfileAvatarController from '@/actions/App/Http/Controllers/Settings/ProfileAvatarController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ALLOWED_FILE_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

type AvatarUploadProps = {
    user: User;
};

export function AvatarUpload({ user }: AvatarUploadProps) {
    const getInitials = useInitials();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [isRemoving, setIsRemoving] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        event.target.value = '';

        if (!file) {
            return;
        }

        if (!ALLOWED_FILE_TYPES.includes(file.type)) {
            toast.error(
                'Please select a valid image file (JPEG, PNG, WebP or GIF)',
            );

            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            toast.error('File size must be less than 10MB');

            return;
        }

        router.post(
            ProfileAvatarController.update.url(),
            { avatar: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setIsLoading(true),
                onError: (errors) => {
                    toast.error(
                        errors.avatar ?? 'The avatar could not be uploaded.',
                    );
                },
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleRemove = () => {
        router.delete(ProfileAvatarController.destroy.url(), {
            preserveScroll: true,
            onStart: () => setIsRemoving(true),
            onSuccess: () => setShowDeleteDialog(false),
            onFinish: () => setIsRemoving(false),
        });
    };

    const isBusy = isLoading || isRemoving;

    return (
        <>
            <div className="space-y-4">
                <h3 className="text-lg font-medium">Profile picture</h3>

                <div className="relative w-fit">
                    <Avatar className="h-24 w-24">
                        <AvatarImage
                            src={user.avatar_url ?? undefined}
                            alt={user.name}
                        />
                        <AvatarFallback className="text-xl">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>

                    {isLoading && (
                        <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                            <Spinner className="size-6 text-white" />
                        </div>
                    )}

                    <div className="absolute -right-1 -bottom-1">
                        <DropdownMenu>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            size="sm"
                                            disabled={isBusy}
                                            className="h-8 w-8 rounded-full border border-border bg-background shadow-sm hover:bg-accent dark:bg-background hover:dark:bg-accent"
                                            variant="outline"
                                            aria-label="Edit avatar"
                                        >
                                            <Edit className="h-3.5 w-3.5" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                </TooltipTrigger>
                                <TooltipContent side="right">
                                    Edit avatar
                                </TooltipContent>
                            </Tooltip>

                            <DropdownMenuContent align="start" className="w-48">
                                <DropdownMenuItem
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                    disabled={isBusy}
                                    className="cursor-pointer"
                                >
                                    <Upload className="h-4 w-4" />
                                    Upload a photo...
                                </DropdownMenuItem>

                                {user.avatar_url && (
                                    <DropdownMenuItem
                                        onClick={() =>
                                            setShowDeleteDialog(true)
                                        }
                                        disabled={isBusy}
                                        variant="destructive"
                                        className="cursor-pointer"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        Remove photo
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            <input
                ref={fileInputRef}
                type="file"
                accept={ALLOWED_FILE_TYPES.join(',')}
                onChange={handleFileSelect}
                className="hidden"
                aria-label="Upload avatar image"
            />

            <AlertDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remove avatar</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to remove your current avatar?
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={(event) => {
                                event.preventDefault();
                                handleRemove();
                            }}
                            disabled={isRemoving}
                            variant="destructive"
                        >
                            {isRemoving ? (
                                <>
                                    <Spinner className="size-4" />
                                    Removing...
                                </>
                            ) : (
                                'Remove avatar'
                            )}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
