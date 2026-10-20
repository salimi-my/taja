import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { LogoutButton } from '@/components/logout-button';
import { NavGroup } from '@/components/nav-group';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenuButton,
    SidebarRail,
    useSidebar,
} from '@/components/ui/sidebar';
import { getNavGroups } from '@/lib/nav';
import { dashboard } from '@/routes';

export function AppSidebar() {
    const { auth } = usePage().props;
    const { setOpenMobile } = useSidebar();
    const isAdmin = auth.user?.role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="floating">
            <SidebarHeader>
                <SidebarMenuButton
                    size="lg"
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    asChild
                >
                    <Link
                        href={dashboard()}
                        prefetch
                        onClick={() => setOpenMobile(false)}
                    >
                        <AppLogo />
                    </Link>
                </SidebarMenuButton>
            </SidebarHeader>

            <SidebarContent>
                {getNavGroups(isAdmin).map((group) => (
                    <NavGroup key={group.title} {...group} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <LogoutButton />
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
