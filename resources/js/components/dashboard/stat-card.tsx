import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { TrendingDown, TrendingUp } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type StatCardProps = {
    title: string;
    value: number;
    icon: LucideIcon;
    /** Tints the icon, e.g. `text-blue-600`. */
    iconClassName?: string;
    /** Tints the number, e.g. to flag a count that needs attention. */
    valueClassName?: string;
    /** Where the card leads. Cards without a destination are not clickable. */
    href?: NonNullable<InertiaLinkProps['href']>;
    /** The line under the number, usually a badge followed by a short caption. */
    children?: ReactNode;
};

/** A count with an icon and a footer line, linking to the list it counts. */
export function StatCard({
    title,
    value,
    icon: Icon,
    iconClassName,
    valueClassName,
    href,
    children,
}: StatCardProps) {
    const card = (
        <Card
            className={cn(
                'h-full gap-4 border-sidebar-border max-md:gap-2 max-md:py-3',
                href && 'transition-colors hover:bg-accent/50',
            )}
        >
            <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                <CardTitle className="text-xs font-medium md:text-sm">
                    {title}
                </CardTitle>
                <Icon
                    aria-hidden="true"
                    className={cn(
                        'size-4 text-muted-foreground',
                        iconClassName,
                    )}
                />
            </CardHeader>
            <CardContent className="max-md:px-4">
                <div
                    className={cn(
                        'text-lg font-bold tabular-nums md:text-2xl',
                        valueClassName,
                    )}
                >
                    {value.toLocaleString()}
                </div>
                <div className="mt-1 flex min-w-0 items-center gap-2">
                    {children}
                </div>
            </CardContent>
        </Card>
    );

    if (!href) {
        return card;
    }

    return (
        <Link
            href={href}
            prefetch
            className="block min-w-0 rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            {card}
        </Link>
    );
}

/** A caption that sits next to a badge in the stat card footer. */
export function StatCaption({ children }: { children: ReactNode }) {
    return <p className="truncate text-xs text-muted-foreground">{children}</p>;
}

type TrendBadgeProps = {
    current: number;
    previous: number;
};

/** Show how a count moved against the period before, e.g. an up arrow and `+3`. */
export function TrendBadge({ current, previous }: TrendBadgeProps) {
    const difference = current - previous;

    if (difference === 0) {
        return (
            <Badge variant="outline" className="text-xs leading-none">
                No change
            </Badge>
        );
    }

    const isUp = difference > 0;
    const Icon = isUp ? TrendingUp : TrendingDown;

    return (
        <Badge variant="secondary" className="text-xs leading-none">
            <Icon
                aria-hidden="true"
                className={isUp ? 'text-green-600' : 'text-red-600'}
            />
            {isUp ? '+' : '-'}
            {Math.abs(difference).toLocaleString()}
        </Badge>
    );
}
