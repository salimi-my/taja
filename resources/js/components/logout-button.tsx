import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { SidebarMenuButton, useSidebar } from '@/components/ui/sidebar';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';

export function LogoutButton() {
    const { state } = useSidebar();
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <SidebarMenuButton asChild tooltip="Log out">
            <Button
                variant="outline"
                className="h-8 w-full cursor-pointer bg-sidebar dark:bg-sidebar"
                asChild
            >
                <Link
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut />
                    {state !== 'collapsed' && <span>Log out</span>}
                </Link>
            </Button>
        </SidebarMenuButton>
    );
}
