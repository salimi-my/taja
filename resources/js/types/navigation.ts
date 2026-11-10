import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

type SidebarNavBase = {
    title: string;
    icon?: LucideIcon | null;
    badge?: string;
    /** Hide the item from users that do not have the admin role. */
    adminOnly?: boolean;
};

export type SidebarNavSubItem = SidebarNavBase & {
    href: NonNullable<InertiaLinkProps['href']>;
    /**
     * Also mark the item as active when this returns true for the current
     * page props, for pages whose url does not start with `href`.
     */
    isActiveForProps?: (props: Record<string, unknown>) => boolean;
    /** Only mark the item as active on its exact url, not on pages below it. */
    exact?: boolean;
};

export type SidebarNavLink = SidebarNavBase & {
    href: NonNullable<InertiaLinkProps['href']>;
    /** Extra urls that also mark this item as active, such as sibling pages. */
    activeHrefs?: NonNullable<InertiaLinkProps['href']>[];
    /** Only mark the item as active on its exact url, not on pages below it. */
    exact?: boolean;
    items?: never;
};

export type SidebarNavCollapsible = SidebarNavBase & {
    items: SidebarNavSubItem[];
    href?: never;
};

export type SidebarNavItem = SidebarNavLink | SidebarNavCollapsible;

export type SidebarNavGroup = {
    title: string;
    items: SidebarNavItem[];
};
