import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { ShieldCheck, UserRound, Users } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useInitials } from '@/hooks/use-initials';
import { index } from '@/routes/users';
import type { ManagedUser } from '@/types';

type RecentUsersProps = {
    users: ManagedUser[];
    /** Whether to link to the users page, which only admins can open. */
    canViewUsers: boolean;
};

export function RecentUsers({ users, canViewUsers }: RecentUsersProps) {
    const getInitials = useInitials();

    return (
        <Card className="border-sidebar-border max-md:py-4">
            <CardHeader className="flex flex-row items-center justify-between gap-2 max-md:px-4">
                <div className="grid gap-1">
                    <CardTitle className="max-md:text-sm">
                        Recent Users
                    </CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        The latest users to join
                    </CardDescription>
                </div>
                {canViewUsers && (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index()}>View all</Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent className="max-md:px-4">
                {users.length === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Users />
                            </EmptyMedia>
                            <EmptyTitle>No users yet</EmptyTitle>
                            <EmptyDescription>
                                New users will show up here.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <div className="space-y-3">
                        {users.map((user) => (
                            <div
                                key={user.id}
                                className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50"
                            >
                                <Avatar className="h-9 w-9 rounded-full">
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
                                    <p className="truncate text-sm leading-tight font-semibold">
                                        {user.name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </p>
                                </div>
                                <div className="flex shrink-0 flex-col items-end gap-1">
                                    <Badge
                                        variant={
                                            user.role === 'admin'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="px-1.5 text-[10px] leading-none"
                                    >
                                        {user.role === 'admin' ? (
                                            <ShieldCheck className="size-3" />
                                        ) : (
                                            <UserRound className="size-3" />
                                        )}
                                        {user.role_label}
                                    </Badge>
                                    <span className="text-[10px] text-muted-foreground">
                                        {user.created_at
                                            ? format(
                                                  new Date(user.created_at),
                                                  'dd MMM yyyy',
                                              )
                                            : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
