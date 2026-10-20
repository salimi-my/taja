import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, KeyRound, LogOut, UserRound } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

export function ProfileDropdown() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const cleanup = useMobileNavigation();
    const user = auth.user;

    if (!user) {
        return null;
    }

    const avatarSrc = user.avatar_url?.trim() ? user.avatar_url : undefined;

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="-mr-[2px] h-9 cursor-pointer p-[2px]! hover:bg-sidebar-accent focus-visible:ring-0"
                    data-test="profile-dropdown-trigger"
                >
                    <Avatar className="size-8">
                        <AvatarImage src={avatarSrc} alt={user.name} />
                        <AvatarFallback className="bg-background">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="hidden flex-1 text-left text-sm leading-tight lg:grid">
                        <span className="max-w-[120px] truncate font-semibold">
                            {user.name}
                        </span>
                    </div>
                    <ChevronDown className="ml-auto hidden size-4 lg:block" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="min-w-56 rounded-lg"
                side="bottom"
                align="end"
                sideOffset={4}
            >
                <DropdownMenuLabel className="p-0 font-normal">
                    <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                        <Avatar className="size-8">
                            <AvatarImage src={avatarSrc} alt={user.name} />
                            <AvatarFallback className="bg-background">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="grid flex-1 text-left text-sm leading-tight">
                            <span className="truncate font-semibold">
                                {user.name}
                            </span>
                            <span className="truncate text-xs">
                                {user.email}
                            </span>
                        </div>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <DropdownMenuItem className="cursor-pointer" asChild>
                        <Link
                            href={editProfile()}
                            prefetch
                            className="flex items-center"
                            onClick={cleanup}
                        >
                            <UserRound />
                            Profile
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem className="cursor-pointer" asChild>
                        <Link
                            href={editSecurity()}
                            prefetch
                            className="flex items-center"
                            onClick={cleanup}
                        >
                            <KeyRound />
                            Security
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem className="cursor-pointer" asChild>
                    <Link
                        href={logout()}
                        as="button"
                        className="flex w-full items-center"
                        onClick={handleLogout}
                        data-test="header-logout-button"
                    >
                        <LogOut />
                        Log out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
