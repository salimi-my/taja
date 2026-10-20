import { Link } from '@inertiajs/react';
import { Ellipsis, UserIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserDelete } from '@/components/users/user-delete';
import { UserEdit } from '@/components/users/user-edit';
import { UserResendPasswordReset } from '@/components/users/user-resend-password-reset';
import { edit as editProfile } from '@/routes/profile';
import type { ManagedUser, RoleOption } from '@/types';

type UserCellActionsProps = {
    user: ManagedUser;
    roles: RoleOption[];
};

export function UserCellActions({ user, roles }: UserCellActionsProps) {
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
                {user.is_current_user ? (
                    <DropdownMenuItem asChild>
                        <Link
                            href={editProfile()}
                            className="cursor-pointer text-sm font-medium"
                        >
                            <UserIcon
                                className="mr-2 size-4 text-sm font-medium"
                                aria-hidden="true"
                            />
                            Profile
                        </Link>
                    </DropdownMenuItem>
                ) : (
                    <>
                        <DropdownMenuItem asChild>
                            <UserEdit user={user} roles={roles} />
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <UserResendPasswordReset user={user} />
                        </DropdownMenuItem>
                    </>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <UserDelete user={user} />
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
