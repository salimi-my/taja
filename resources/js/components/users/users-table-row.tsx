import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    CircleCheckBig,
    CircleX,
    FileSearch,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import { memo, useCallback, useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TableCell, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import { UserCellActions } from '@/components/users/user-cell-actions';
import { UserForm } from '@/components/users/user-form';
import { edit as editProfile } from '@/routes/profile';
import type { ManagedUser, RoleOption } from '@/types';

type UserTableRowItemProps = {
    user: ManagedUser;
    roles: RoleOption[];
    isSelected: boolean;
    onToggleSelect: (id: number) => void;
    onUserClick: (user: ManagedUser) => void;
};

const UserTableRowItem = memo(function UserTableRowItem({
    user,
    roles,
    isSelected,
    onToggleSelect,
    onUserClick,
}: UserTableRowItemProps) {
    const getInitials = useInitials();
    const isAdmin = user.role === 'admin';

    return (
        <TableRow>
            <TableCell>
                <Checkbox
                    aria-label={`Select ${user.name}`}
                    checked={isSelected}
                    onCheckedChange={() => onToggleSelect(user.id)}
                    disabled={user.is_current_user}
                />
            </TableCell>
            <TableCell>
                <button
                    type="button"
                    onClick={() => onUserClick(user)}
                    className="group/user flex cursor-pointer items-center gap-2 px-1 py-1.5 text-left text-sm"
                >
                    <Avatar className="h-8 w-8 rounded-full transition-transform duration-300 ease-in-out group-hover/user:scale-[1.15]">
                        <AvatarImage
                            src={user.avatar_url ?? ''}
                            alt={user.name}
                            loading="lazy"
                        />
                        <AvatarFallback className="rounded-full bg-zinc-200/50 dark:bg-zinc-700/60">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                        <div className="text-left font-semibold">
                            <span className="relative truncate after:absolute after:-bottom-0.5 after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-primary after:transition-transform after:duration-300 after:ease-in-out group-hover/user:after:origin-bottom-left group-hover/user:after:scale-x-100">
                                {user.name}
                            </span>
                            {user.is_current_user && (
                                <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                    (You)
                                </span>
                            )}
                        </div>
                    </div>
                </button>
            </TableCell>
            <TableCell>
                <a
                    href={`mailto:${user.email}`}
                    className="link-hover after:h-[1px]"
                >
                    {user.email}
                </a>
            </TableCell>
            <TableCell>
                <Badge
                    variant={isAdmin ? 'default' : 'outline'}
                    className="min-w-20 text-nowrap"
                >
                    {isAdmin ? <ShieldCheck /> : <UserRound />}{' '}
                    {user.role_label}
                </Badge>
            </TableCell>
            <TableCell>
                {user.email_verified_at ? (
                    <Badge
                        variant="outline"
                        className="min-w-[90px] border-green-500 text-nowrap text-green-500 dark:border-green-400 dark:text-green-400"
                    >
                        <CircleCheckBig className="size-4" /> Verified
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="min-w-[90px] text-nowrap"
                    >
                        <CircleX className="size-4" /> Unverified
                    </Badge>
                )}
            </TableCell>
            <TableCell>
                {user.created_at
                    ? format(new Date(user.created_at), 'dd MMM yyyy')
                    : 'N/A'}
            </TableCell>
            <TableCell>
                <UserCellActions user={user} roles={roles} />
            </TableCell>
        </TableRow>
    );
});

type UsersTableRowProps = {
    users: ManagedUser[];
    roles: RoleOption[];
    selectedIds: Set<number>;
    handleToggleSelect: (id: number) => void;
};

export function UsersTableRow({
    users,
    roles,
    selectedIds,
    handleToggleSelect,
}: UsersTableRowProps) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<ManagedUser | null>(null);

    const handleUserClick = useCallback((user: ManagedUser) => {
        if (user.is_current_user) {
            router.visit(editProfile());

            return;
        }

        setSelectedUser(user);
        setEditDialogOpen(true);
    }, []);

    return (
        <>
            {users.map((user) => (
                <UserTableRowItem
                    key={user.id}
                    user={user}
                    roles={roles}
                    isSelected={selectedIds.has(user.id)}
                    onToggleSelect={handleToggleSelect}
                    onUserClick={handleUserClick}
                />
            ))}

            {users.length === 0 && (
                <TableRow className="hover:bg-transparent">
                    <TableCell colSpan={999}>
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <FileSearch />
                                </EmptyMedia>
                                <EmptyTitle>No results found</EmptyTitle>
                                <EmptyDescription>
                                    Try adjusting your search or filters to find
                                    what you're looking for.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    </TableCell>
                </TableRow>
            )}

            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>
                            Make changes to user here. Click save when you're
                            done.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedUser && (
                        <UserForm
                            key={selectedUser.id}
                            user={selectedUser}
                            roles={roles}
                            onSuccess={() => setEditDialogOpen(false)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
