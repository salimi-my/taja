import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type {
    SidebarNavCollapsible,
    SidebarNavGroup,
    SidebarNavLink,
    SidebarNavSubItem,
} from '@/types';
import { toUrl } from '@/lib/utils';

export function NavGroup({ title, items }: SidebarNavGroup) {
    const { state, isMobile } = useSidebar();

    return (
        <SidebarGroup>
            <SidebarGroupLabel>{title}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    if (!item.items) {
                        return <SidebarMenuLink key={item.title} item={item} />;
                    }

                    if (state === 'collapsed' && !isMobile) {
                        return (
                            <SidebarMenuCollapsedDropdown
                                key={item.title}
                                item={item}
                            />
                        );
                    }

                    return (
                        <SidebarMenuCollapsible key={item.title} item={item} />
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}

/**
 * Find the sub menu item to highlight. When several items match the current
 * url (for example `/posts` and `/posts/create`), the most specific one wins.
 */
function useActiveSubItem(
    items: SidebarNavSubItem[],
): SidebarNavSubItem | undefined {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return items
        .filter((subItem) => isCurrentOrParentUrl(subItem.href))
        .sort((a, b) => toUrl(b.href).length - toUrl(a.href).length)[0];
}

function NavBadge({ children }: { children: ReactNode }) {
    return <Badge className="rounded-full px-1 py-0 text-xs">{children}</Badge>;
}

function SidebarMenuLink({ item }: { item: SidebarNavLink }) {
    const { setOpenMobile } = useSidebar();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={[item.href, ...(item.activeHrefs ?? [])].some(
                    (href) => isCurrentOrParentUrl(href),
                )}
                tooltip={item.title}
            >
                <Link
                    href={item.href}
                    prefetch
                    onClick={() => setOpenMobile(false)}
                >
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                    {item.badge && <NavBadge>{item.badge}</NavBadge>}
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function SidebarMenuCollapsible({ item }: { item: SidebarNavCollapsible }) {
    const { setOpenMobile } = useSidebar();
    const activeSubItem = useActiveSubItem(item.items);

    return (
        <Collapsible
            asChild
            defaultOpen={activeSubItem !== undefined}
            className="group/collapsible"
        >
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        className="cursor-pointer"
                        tooltip={item.title}
                    >
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                        {item.badge && <NavBadge>{item.badge}</NavBadge>}
                        <Plus className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-135" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent className="CollapsibleContent">
                    <SidebarMenuSub>
                        {item.items.map((subItem) => (
                            <SidebarMenuSubItem key={subItem.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    isActive={subItem === activeSubItem}
                                >
                                    <Link
                                        href={subItem.href}
                                        prefetch
                                        onClick={() => setOpenMobile(false)}
                                    >
                                        {subItem.icon && <subItem.icon />}
                                        <span>{subItem.title}</span>
                                        {subItem.badge && (
                                            <NavBadge>{subItem.badge}</NavBadge>
                                        )}
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}

function SidebarMenuCollapsedDropdown({
    item,
}: {
    item: SidebarNavCollapsible;
}) {
    const activeSubItem = useActiveSubItem(item.items);

    return (
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <SidebarMenuButton
                        className="cursor-pointer"
                        tooltip={item.title}
                        isActive={activeSubItem !== undefined}
                    >
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                        {item.badge && <NavBadge>{item.badge}</NavBadge>}
                        <Plus className="ml-auto" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent side="right" align="start" sideOffset={4}>
                    <DropdownMenuLabel>
                        {item.title} {item.badge ? `(${item.badge})` : ''}
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {item.items.map((subItem) => (
                        <DropdownMenuItem key={subItem.title} asChild>
                            <Link
                                href={subItem.href}
                                prefetch
                                className={
                                    subItem === activeSubItem
                                        ? 'cursor-pointer bg-secondary'
                                        : 'cursor-pointer'
                                }
                            >
                                {subItem.icon && <subItem.icon />}
                                <span className="max-w-52 text-wrap">
                                    {subItem.title}
                                </span>
                                {subItem.badge && (
                                    <span className="ml-auto text-xs">
                                        {subItem.badge}
                                    </span>
                                )}
                            </Link>
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    );
}
