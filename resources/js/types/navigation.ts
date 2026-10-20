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

export type SidebarNavSubItem = Omit<SidebarNavBase, 'adminOnly'> & {
    href: NonNullable<InertiaLinkProps['href']>;
};

export type SidebarNavLink = SidebarNavBase & {
    href: NonNullable<InertiaLinkProps['href']>;
    /** Extra urls that also mark this item as active, such as sibling pages. */
    activeHrefs?: NonNullable<InertiaLinkProps['href']>[];
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
