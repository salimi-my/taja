import { CircleCheckBig, CircleX } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { Publisher } from '@/types';

type PublisherStatusBadgeProps = {
    publisher: Pick<Publisher, 'status' | 'status_label'>;
    className?: string;
};

export function PublisherStatusBadge({
    publisher,
    className,
}: PublisherStatusBadgeProps) {
    if (publisher.status === 'active') {
        return (
            <Badge
                variant="outline"
                className={`min-w-[80px] border-green-500 text-nowrap text-green-500 dark:border-green-400 dark:text-green-400 ${className ?? ''}`}
            >
                <CircleCheckBig className="size-4" /> {publisher.status_label}
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className={`min-w-[80px] text-nowrap ${className ?? ''}`}
        >
            <CircleX className="size-4" /> {publisher.status_label}
        </Badge>
    );
}

type RightsTypeBadgesProps = {
    rightsTypes: string[];
    labels: Record<string, string>;
};

export function RightsTypeBadges({
    rightsTypes,
    labels,
}: RightsTypeBadgesProps) {
    return (
        <div className="flex flex-wrap gap-1">
            {rightsTypes.map((type) => (
                <Badge
                    key={type}
                    variant="secondary"
                    className="text-[11px] font-normal"
                >
                    {labels[type] ?? type}
                </Badge>
            ))}
        </div>
    );
}
