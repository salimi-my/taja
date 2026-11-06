import { Badge } from '@/components/ui/badge';
import { importTypeIcons } from '@/lib/imports';
import { cn } from '@/lib/utils';
import type { ImportType } from '@/types';

type ImportTypeBadgeProps = {
    type: ImportType;
    label: string;
    className?: string;
};

/** The kind of data an import creates, with the icon of its sidebar menu item. */
export function ImportTypeBadge({
    type,
    label,
    className,
}: ImportTypeBadgeProps) {
    const Icon = importTypeIcons[type];

    return (
        <Badge variant="secondary" className={cn('text-nowrap', className)}>
            <Icon className="size-3.5" />
            {label}
        </Badge>
    );
}
