import { usePage } from '@inertiajs/react';
import { ModeToggle } from '@/components/mode-toggle';
import { ProfileDropdown } from '@/components/profile-dropdown';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useWindowScroll } from '@/hooks/use-window-scroll';
import { navGroups } from '@/lib/nav';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type HeaderLayoutOptions = {
    scrolled: boolean;
};

function getHeaderLayoutClasses({ scrolled }: HeaderLayoutOptions): string {
    return cn(
        'transition-[width,left,right] ease-linear',
        scrolled ? 'max-md:left-[15px]' : 'max-md:left-2',
        scrolled ? 'md:right-[15px]' : 'md:right-[0.3125rem]',
        scrolled
            ? 'max-md:w-[calc(100%_-_30px)]'
            : 'max-md:w-[calc(100%_-_16px)]',
        scrolled
            ? 'md:group-has-[[data-state=expanded]]/sidebar-wrapper:w-[calc(100%_-_var(--sidebar-width)_-_30px)]'
            : 'md:group-has-[[data-state=expanded]]/sidebar-wrapper:w-[calc(100%_-_var(--sidebar-width)_-_12px)]',
        scrolled
            ? 'md:group-has-[[data-state=collapsed]]/sidebar-wrapper:group-has-[[data-collapsible=icon]]/sidebar-wrapper:w-[calc(100%_-_(var(--sidebar-width-icon)_+_--spacing(4)_+_2px_+_28px))]'
            : 'md:group-has-[[data-state=collapsed]]/sidebar-wrapper:group-has-[[data-collapsible=icon]]/sidebar-wrapper:w-[calc(100%_-_(var(--sidebar-width-icon)_+_--spacing(4)_+_2px_+_10px))]',
    );
}

const maxHeaderLayoutClasses = getHeaderLayoutClasses({ scrolled: false });

/**
 * Resolve the page title: the last breadcrumb wins, then the matching
 * sidebar item, then the application name.
 */
function usePageTitle(breadcrumbs: BreadcrumbItem[]): string {
    const { name } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    if (breadcrumbs.length > 0) {
        return breadcrumbs[breadcrumbs.length - 1].title;
    }

    const flatItems = navGroups
        .flatMap((group) => group.items)
        .flatMap((item) => (item.items ? item.items : [item]));

    const match = flatItems.find((item) => isCurrentOrParentUrl(item.href));

    return match?.title ?? name;
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItem[];
}) {
    const { y } = useWindowScroll();
    const title = usePageTitle(breadcrumbs);
    const scrolled = Boolean(y && y > 20);

    return (
        <>
            <div
                className={cn(
                    'fixed top-0 z-10 h-8 bg-header',
                    maxHeaderLayoutClasses,
                )}
            />
            <header
                className={cn(
                    'fixed top-2 z-10 flex shrink-0 flex-col justify-center rounded-lg border border-header bg-header px-2.5',
                    getHeaderLayoutClasses({ scrolled }),
                    scrolled && 'border-sidebar-border bg-sidebar shadow-md',
                )}
            >
                <div className="flex h-12 shrink-0 items-center justify-between">
                    <div className="flex items-center gap-2">
                        <SidebarTrigger
                            className="-ml-[2px] size-8 bg-sidebar dark:bg-sidebar"
                            variant="outline"
                        />
                        <Separator
                            orientation="vertical"
                            className="mx-2 h-6 self-center!"
                        />
                        <span className="truncate font-medium">{title}</span>
                    </div>
                    <div className="flex items-center gap-2.5">
                        <ModeToggle />
                        <ProfileDropdown />
                    </div>
                </div>
            </header>
        </>
    );
}
