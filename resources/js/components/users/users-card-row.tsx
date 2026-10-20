import { format } from 'date-fns';
import {
    Calendar,
    CircleCheckBig,
    CircleX,
    FileSearch,
    Mail,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import { memo } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useInitials } from '@/hooks/use-initials';
import { UserCellActions } from '@/components/users/user-cell-actions';
import type { ManagedUser, RoleOption } from '@/types';

type UserCardRowItemProps = {
    user: ManagedUser;
    roles: RoleOption[];
    isSelected: boolean;
    onToggleSelect: (id: number) => void;
};

const UserCardRowItem = memo(function UserCardRowItem({
    user,
    roles,
    isSelected,
    onToggleSelect,
}: UserCardRowItemProps) {
    const getInitials = useInitials();
    const isAdmin = user.role === 'admin';

    return (
        <div className="relative flex flex-col gap-3 rounded-lg border p-3">
            <div className="flex items-start gap-3">
                <Checkbox
                    aria-label={`Select ${user.name}`}
                    checked={isSelected}
                    onCheckedChange={() => onToggleSelect(user.id)}
                    disabled={user.is_current_user}
                    className="mt-1 disabled:bg-muted-foreground/10"
                />

                <div className="flex flex-1 flex-col gap-1.5">
                    <div className="flex items-start gap-3">
                        <Avatar className="h-10 w-10 rounded-full">
                            <AvatarImage
                                src={user.avatar_url ?? ''}
                                alt={user.name}
                                loading="lazy"
                            />
                            <AvatarFallback className="rounded-full bg-zinc-200/50 dark:bg-zinc-700/60">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="min-w-0 flex-1">
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center text-sm leading-tight font-semibold">
                                        <span className="inline-block max-w-[130px] truncate">
                                            {user.name}
                                        </span>
                                        {user.is_current_user && (
                                            <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                                (You)
                                            </span>
                                        )}
                                    </p>

                                    <Badge
                                        variant={
                                            isAdmin ? 'default' : 'outline'
                                        }
                                        className="px-1.5 text-[10px] leading-none text-nowrap"
                                    >
                                        {isAdmin ? (
                                            <ShieldCheck className="size-3" />
                                        ) : (
                                            <UserRound className="size-3" />
                                        )}
                                        {user.role_label}
                                    </Badge>
                                </div>
                                <UserCellActions user={user} roles={roles} />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 text-xs">
                        <Mail className="size-3 flex-shrink-0 text-muted-foreground" />
                        <a
                            href={`mailto:${user.email}`}
                            className="link-hover max-w-[220px] truncate after:h-px"
                        >
                            {user.email}
                        </a>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {user.email_verified_at ? (
                            <Badge
                                variant="outline"
                                className="border-green-500 px-1.5 text-[10px] leading-none text-nowrap text-green-500 dark:border-green-400 dark:text-green-400"
                            >
                                <CircleCheckBig className="size-3" /> Verified
                            </Badge>
                        ) : (
                            <Badge
                                variant="outline"
                                className="px-1.5 text-[10px] leading-none text-nowrap"
                            >
                                <CircleX className="size-3" /> Unverified
                            </Badge>
                        )}

                        <Badge
                            variant="secondary"
                            className="text-[10px] font-normal text-nowrap"
                        >
                            <Calendar className="size-3" />
                            {user.created_at
                                ? format(
                                      new Date(user.created_at),
                                      'dd MMM yyyy',
                                  )
                                : 'N/A'}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>
    );
});

type UsersCardRowProps = {
    users: ManagedUser[];
    roles: RoleOption[];
    selectedIds: Set<number>;
    handleToggleSelect: (id: number) => void;
};

export function UsersCardRow({
    users,
    roles,
    selectedIds,
    handleToggleSelect,
}: UsersCardRowProps) {
    if (users.length === 0) {
        return (
            <Empty className="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <FileSearch />
                    </EmptyMedia>
                    <EmptyTitle>No results found</EmptyTitle>
                    <EmptyDescription>
                        Try adjusting your search or filters to find what you're
                        looking for.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="space-y-3">
            {users.map((user) => (
                <UserCardRowItem
                    key={user.id}
                    user={user}
                    roles={roles}
                    isSelected={selectedIds.has(user.id)}
                    onToggleSelect={handleToggleSelect}
                />
            ))}
        </div>
    );
}
