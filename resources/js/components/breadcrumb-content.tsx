import { Link } from '@inertiajs/react';
import { Home } from 'lucide-react';
import { Fragment } from 'react';
import {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/** Collapse the trail into an ellipsis menu above this many items. */
const MAX_VISIBLE_ITEMS = 3;
/** How many leading items stay visible when the trail is collapsed. */
const LEADING_ITEMS = 2;

export function BreadcrumbContent({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    if (breadcrumbs.length === 0) {
        return null;
    }

    const shouldCollapse = breadcrumbs.length > MAX_VISIBLE_ITEMS;
    const lastItem = breadcrumbs[breadcrumbs.length - 1];
    const leadingItems = shouldCollapse
        ? breadcrumbs.slice(0, LEADING_ITEMS)
        : breadcrumbs.slice(0, -1);
    const collapsedItems = shouldCollapse
        ? breadcrumbs.slice(LEADING_ITEMS, -1)
        : [];

    return (
        <Breadcrumb>
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink asChild>
                        <Link href={dashboard()} className="flex items-center">
                            <Home className="mr-2 h-4 w-4" />
                            Home
                        </Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />

                {leadingItems.map((item) => (
                    <Fragment key={item.title}>
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link href={item.href}>{item.title}</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                    </Fragment>
                ))}

                {collapsedItems.length > 0 && (
                    <>
                        <BreadcrumbItem>
                            <DropdownMenu>
                                <DropdownMenuTrigger className="flex cursor-pointer items-center gap-1 rounded-sm p-1 hover:bg-muted">
                                    <BreadcrumbEllipsis className="h-4 w-4" />
                                    <span className="sr-only">Toggle menu</span>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start">
                                    {collapsedItems.map((item) => (
                                        <DropdownMenuItem
                                            key={item.title}
                                            asChild
                                        >
                                            <Link
                                                href={item.href}
                                                className="cursor-pointer"
                                            >
                                                {item.title}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                    </>
                )}

                <BreadcrumbItem>
                    <BreadcrumbPage>{lastItem.title}</BreadcrumbPage>
                </BreadcrumbItem>
            </BreadcrumbList>
        </Breadcrumb>
    );
}
