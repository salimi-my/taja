import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    CheckCircle2,
    FileWarning,
    PieChart,
    ShieldAlert,
    TriangleAlert,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { show as showImport } from '@/routes/imports';
import { incompleteSplit } from '@/routes/reports';
import { index as worksIndex } from '@/routes/works';
import type { AttentionItem, AttentionKey } from '@/types';

type Href = NonNullable<InertiaLinkProps['href']>;

type AttentionConfig = {
    icon: LucideIcon;
    title: string;
    description: string;
    href: (item: AttentionItem) => Href | null;
};

/** Every conflict status except "no conflict", as the works filter expects them. */
const CONFLICT_STATUSES = [
    'share_conflict',
    'ownership_conflict',
    'duplicate',
    'society_query',
];

const attentionConfig: Record<AttentionKey, AttentionConfig> = {
    incomplete_splits: {
        icon: PieChart,
        title: 'Incomplete splits',
        description: 'Works without parties or with shares not at 100%',
        href: () => incompleteSplit(),
    },
    works_in_conflict: {
        icon: ShieldAlert,
        title: 'Works in conflict',
        description: 'A share, ownership, duplicate or society query',
        href: () =>
            worksIndex({ query: { conflict_statuses: CONFLICT_STATUSES } }),
    },
    rejected_registrations: {
        icon: TriangleAlert,
        title: 'Rejected registrations',
        description: 'Rejected by the society and not resolved',
        href: () =>
            worksIndex({ query: { registration_statuses: ['rejected'] } }),
    },
    failed_imports: {
        icon: FileWarning,
        title: 'Failed imports',
        description: 'A CSV import stopped before it finished',
        href: (item) =>
            item.target_id === null ? null : showImport(item.target_id),
    },
};

type AttentionListProps = {
    items: AttentionItem[];
};

/** The checks on the catalogue, each linking to the records that fail it. */
export function AttentionList({ items }: AttentionListProps) {
    const pending = items.filter((item) => item.count > 0).length;

    return (
        <Card className="flex h-full flex-col border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">
                    Needs attention
                </CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    Things to review in the catalogue right now
                </CardDescription>
            </CardHeader>
            <CardContent className="flex-1 max-md:px-4">
                <ul className="grid h-full auto-rows-fr gap-3">
                    {items.map((item) => {
                        const {
                            icon: Icon,
                            title,
                            description,
                            href,
                        } = attentionConfig[item.key];
                        const needsAttention = item.count > 0;
                        const target = href(item);

                        const content = (
                            <>
                                <span
                                    className={cn(
                                        'flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground',
                                        needsAttention &&
                                            'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                    )}
                                >
                                    <Icon
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm leading-tight font-medium">
                                        {title}
                                    </p>
                                    <p className="mt-1 truncate text-[9px] md:text-xs text-muted-foreground">
                                        {description}
                                    </p>
                                </div>
                                {needsAttention ? (
                                    <Badge
                                        variant="outline"
                                        className="shrink-0 border-amber-500 text-amber-600 dark:border-amber-400 dark:text-amber-400"
                                    >
                                        {item.count.toLocaleString()}
                                    </Badge>
                                ) : (
                                    <CheckCircle2
                                        aria-label="All clear"
                                        className="size-4 shrink-0 text-green-600"
                                    />
                                )}
                            </>
                        );

                        return (
                            <li key={item.key}>
                                {target === null ? (
                                    <div className="flex h-full items-center gap-3 rounded-lg border p-3">
                                        {content}
                                    </div>
                                ) : (
                                    <Link
                                        href={target}
                                        prefetch
                                        className="flex h-full items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50"
                                    >
                                        {content}
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </CardContent>
            <CardFooter className="text-sm text-muted-foreground max-md:px-4 max-md:text-xs">
                {pending === 0
                    ? "You're all caught up."
                    : `${pending} of ${items.length} checks need a look.`}
            </CardFooter>
        </Card>
    );
}
