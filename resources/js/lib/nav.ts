import {
    FileText,
    Folder,
    Image,
    Images,
    LayoutGrid,
    List,
    MessageSquare,
    Plus,
    Settings,
    Upload,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import {
    index as commentsIndex,
    pending as commentsPending,
} from '@/routes/comments';
import { index as mediaIndex, upload as mediaUpload } from '@/routes/media';
import { create as postsCreate, index as postsIndex } from '@/routes/posts';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as usersIndex } from '@/routes/users';
import type { SidebarNavGroup, SidebarNavItem } from '@/types';

/**
 * The sidebar menu.
 *
 * - A link item has an `href`.
 * - A collapsible item has `items` (sub menu) instead of an `href`.
 * - A link item can list `activeHrefs` so it stays active on related pages.
 * - Set `adminOnly: true` to hide an item from non-admin users.
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
        title: 'Content',
        items: [
            {
                title: 'Posts',
                icon: FileText,
                items: [
                    { title: 'All Posts', href: postsIndex(), icon: List },
                    { title: 'New Post', href: postsCreate(), icon: Plus },
                    {
                        title: 'Categories',
                        href: categoriesIndex(),
                        icon: Folder,
                    },
                ],
            },
            {
                title: 'Media',
                icon: Image,
                items: [
                    { title: 'Library', href: mediaIndex(), icon: Images },
                    { title: 'Upload', href: mediaUpload(), icon: Upload },
                ],
            },
            {
                title: 'Comments',
                icon: MessageSquare,
                items: [
                    { title: 'All Comments', href: commentsIndex() },
                    { title: 'Pending', href: commentsPending() },
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
            items: group.items.filter(
                (item: SidebarNavItem) => !item.adminOnly || isAdmin,
            ),
        }))
        .filter((group) => group.items.length > 0);
}
