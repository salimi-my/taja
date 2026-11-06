import { CircleCheckBig, CircleX } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { Writer } from '@/types';

type WriterStatusBadgeProps = {
    writer: Pick<Writer, 'status' | 'status_label'>;
    className?: string;
};

export function WriterStatusBadge({
    writer,
    className,
}: WriterStatusBadgeProps) {
    if (writer.status === 'active') {
        return (
            <Badge
                variant="outline"
                className={`min-w-[80px] border-green-500 text-nowrap text-green-500 dark:border-green-400 dark:text-green-400 ${className ?? ''}`}
            >
                <CircleCheckBig className="size-4" /> {writer.status_label}
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className={`min-w-[80px] text-nowrap ${className ?? ''}`}
        >
            <CircleX className="size-4" /> {writer.status_label}
        </Badge>
    );
}
