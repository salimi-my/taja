import { format } from 'date-fns';
import type { ReactNode } from 'react';

type DetailItemProps = {
    label: string;
    children: ReactNode;
};

export function DetailItem({ label, children }: DetailItemProps) {
    const isEmpty =
        children === null || children === undefined || children === '';

    return (
        <div className="space-y-1">
            <dt className="text-xs font-medium text-muted-foreground">
                {label}
            </dt>
            <dd className="text-sm font-medium break-words whitespace-pre-line">
                {isEmpty ? (
                    <span className="text-muted-foreground">N/A</span>
                ) : (
                    children
                )}
            </dd>
        </div>
    );
}

export function DetailGrid({ children }: { children: ReactNode }) {
    return (
        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{children}</dl>
    );
}

export function formatDate(value: string | null): string | null {
    return value ? format(new Date(value), 'dd MMM yyyy') : null;
}

export function formatYesNo(value: boolean | null): string | null {
    if (value === null) {
        return null;
    }

    return value ? 'Yes' : 'No';
}

export function formatPercent(value: string | null): string | null {
    return value === null ? null : `${Number(value)}%`;
}
