import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card } from '@/components/ui/card';

/** The first letters of the first two words of a name, in capitals. */
function initialsOf(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

export type ProfileFact = {
    icon: LucideIcon;
    label: string;
    value: ReactNode;
};

type ProfileHeaderProps = {
    name: string;
    /** An icon to show instead of the initials of the name. */
    icon?: LucideIcon;
    /** Codes, status badges and links shown under the name. */
    meta: ReactNode;
    /** A short line shown between the name and `meta`. */
    subtitle?: ReactNode;
    /** The key facts shown in a strip below the name. */
    facts: ProfileFact[];
};

/**
 * The top card of a record's detail page: who it is, its status and the key
 * facts at a glance.
 */
export function ProfileHeader({
    name,
    icon: Icon,
    meta,
    subtitle,
    facts,
}: ProfileHeaderProps) {
    return (
        <Card className="gap-0 border border-sidebar-border py-0 ring-0">
            <div className="flex flex-wrap items-center gap-4 p-6">
                <div className="flex size-16 shrink-0 items-center justify-center rounded-xl bg-primary text-xl font-semibold text-primary-foreground">
                    {Icon ? <Icon className="size-7" /> : initialsOf(name)}
                </div>
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-semibold break-words">
                        {name}
                    </h1>
                    {subtitle && (
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {subtitle}
                        </p>
                    )}
                    <div className="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        {meta}
                    </div>
                </div>
            </div>

            <div className="grid divide-y border-t sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
                {facts.map(({ icon: FactIcon, label, value }) => (
                    <div
                        key={label}
                        className="flex items-center gap-3 px-6 py-4"
                    >
                        <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <FactIcon className="size-4" />
                        </div>
                        <div className="min-w-0">
                            <div className="text-xs text-muted-foreground">
                                {label}
                            </div>
                            <div className="text-sm font-medium break-words">
                                {value || '-'}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </Card>
    );
}

/** A code shown as a small monospace chip. */
export function CodeChip({ children }: { children: ReactNode }) {
    return (
        <span className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs text-foreground">
            {children}
        </span>
    );
}
