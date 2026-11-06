import {
    BarChart3,
    Building2,
    History,
    LayoutGrid,
    Music,
    PenLine,
    Settings,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import {
    incompleteSplit,
    rightsHistory,
    songOwnership,
    writerCatalogue,
} from '@/routes/reports';
import {
    create as importCreate,
    index as importsIndex,
} from '@/routes/imports';
import { edit as editSecurity } from '@/routes/security';
import { index as usersIndex } from '@/routes/users';
import {
    create as createPublisher,
    index as publishersIndex,
} from '@/routes/publishers';
import { create as createWork, index as worksIndex } from '@/routes/works';
import {
    create as createWriter,
    index as writersIndex,
} from '@/routes/writers';
import type { ImportType, SidebarNavGroup, SidebarNavItem } from '@/types';

/**
 * Keep an Import item active on the preview and result pages (`/imports/{id}`),
 * whose url holds the import id instead of the type.
 */
function isImportOf(type: ImportType) {
    return (props: Record<string, unknown>): boolean =>
        (props.csvImport as { data?: { type?: string } } | undefined)?.data
            ?.type === type;
}

/**
 * The sidebar menu.
 *
 * - A link item has an `href`.
 * - A collapsible item has `items` (sub menu) instead of an `href`.
 * - A link item can list `activeHrefs` so it stays active on related pages.
 * - Set `adminOnly: true` on an item or a sub menu item to hide it from
 *   non-admin users.
 * - Always build `href` with Wayfinder route functions, never raw strings.
 */
export const navGroups: SidebarNavGroup[] = [
    {
        title: 'General',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'Catalogue',
        items: [
            {
                title: 'Works',
                icon: Music,
                items: [
                    {
                        title: 'Create New',
                        href: createWork(),
                        adminOnly: true,
                    },
                    { title: 'All Works', href: worksIndex() },
                    {
                        title: 'Import',
                        href: importCreate('works'),
                        isActiveForProps: isImportOf('works'),
                        adminOnly: true,
                    },
                ],
            },
        ],
    },
    {
        title: 'Masters',
        items: [
            {
                title: 'Writers',
                icon: PenLine,
                items: [
                    {
                        title: 'Create New',
                        href: createWriter(),
                        adminOnly: true,
                    },
                    { title: 'All Writers', href: writersIndex() },
                    {
                        title: 'Import',
                        href: importCreate('writers'),
                        isActiveForProps: isImportOf('writers'),
                        adminOnly: true,
                    },
                ],
            },
            {
                title: 'Publishers',
                icon: Building2,
                items: [
                    {
                        title: 'Create New',
                        href: createPublisher(),
                        adminOnly: true,
                    },
                    { title: 'All Publishers', href: publishersIndex() },
                    {
                        title: 'Import',
                        href: importCreate('publishers'),
                        isActiveForProps: isImportOf('publishers'),
                        adminOnly: true,
                    },
                ],
            },
        ],
    },
    {
        title: 'Imports',
        items: [
            {
                title: 'Import History',
                href: importsIndex(),
                icon: History,
                exact: true,
                adminOnly: true,
            },
        ],
    },
    {
        title: 'Reports',
        items: [
            {
                title: 'Reports',
                icon: BarChart3,
                items: [
                    { title: 'Writer Catalogue', href: writerCatalogue() },
                    { title: 'Song Ownership Split', href: songOwnership() },
                    { title: 'Rights History', href: rightsHistory() },
                    { title: 'Incomplete Split', href: incompleteSplit() },
                ],
            },
        ],
    },
    {
        title: 'Account',
        items: [
            {
                title: 'Users',
                href: usersIndex(),
                icon: Users,
                adminOnly: true,
            },
            {
                title: 'Settings',
                href: editProfile(),
                icon: Settings,
                activeHrefs: [editSecurity(), editAppearance()],
            },
        ],
    },
];

/**
 * Get the sidebar groups visible to a user, dropping admin-only items for
 * regular users and any group left without items.
 */
export function getNavGroups(isAdmin: boolean): SidebarNavGroup[] {
    return navGroups
        .map((group) => ({
            ...group,
            items: group.items
                .filter((item: SidebarNavItem) => !item.adminOnly || isAdmin)
                .map((item: SidebarNavItem) =>
                    item.items
                        ? {
                              ...item,
                              items: item.items.filter(
                                  (subItem) => !subItem.adminOnly || isAdmin,
                              ),
                          }
                        : item,
                ),
        }))
        .filter((group) => group.items.length > 0);
}
