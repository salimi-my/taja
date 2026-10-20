import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { tabsListVariants } from '@/components/ui/tabs';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const settingsNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="flex w-full flex-col items-start justify-center gap-4 lg:flex-row">
            <nav
                aria-label="Settings"
                className={cn(
                    tabsListVariants(),
                    'grid h-auto w-full shrink-0 grid-cols-3 gap-1 lg:w-60 lg:grid-cols-1',
                )}
            >
                {settingsNavItems.map((item) => {
                    const isActive = isCurrentOrParentUrl(item.href);

                    return (
                        <Link
                            key={toUrl(item.href)}
                            href={item.href}
                            aria-current={isActive ? 'page' : undefined}
                            className={cn(
                                'inline-flex items-center justify-center rounded-md border border-transparent px-2 py-1 text-sm font-medium whitespace-nowrap text-foreground/60 transition-all hover:text-foreground lg:justify-start dark:text-muted-foreground dark:hover:text-foreground',
                                isActive
                                    ? 'bg-background text-foreground shadow-sm dark:border-input dark:bg-input/30'
                                    : 'hover:bg-sidebar',
                            )}
                        >
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <div className="mt-2 flex w-full flex-col gap-4 lg:mt-0">
                {children}
            </div>
        </div>
    );
}
