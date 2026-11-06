import { Badge } from '@/components/ui/badge';
import type { Work, WorkPartyWork } from '@/types';

type RegistrationBadgeProps = {
    work: Pick<Work, 'registration_status' | 'registration_status_label'>;
    className?: string;
};

/** The registration status of a work, colored by outcome. */
export function WorkRegistrationBadge({
    work,
    className,
}: RegistrationBadgeProps) {
    const tone: Record<string, string> = {
        accepted:
            'border-green-500 text-green-500 dark:border-green-400 dark:text-green-400',
        rejected:
            'border-destructive text-destructive dark:border-destructive dark:text-destructive',
        conflict:
            'border-amber-500 text-amber-500 dark:border-amber-400 dark:text-amber-400',
    };

    return (
        <Badge
            variant="outline"
            className={`text-nowrap ${tone[work.registration_status] ?? ''} ${className ?? ''}`}
        >
            {work.registration_status_label}
        </Badge>
    );
}

type ConflictBadgeProps = {
    work: Pick<
        Work | WorkPartyWork,
        'conflict_status' | 'conflict_status_label'
    >;
    className?: string;
};

/** The conflict status of a work. Shows nothing special when there is none. */
export function WorkConflictBadge({ work, className }: ConflictBadgeProps) {
    if (work.conflict_status === 'no_conflict') {
        return (
            <span className="text-sm text-muted-foreground">
                {work.conflict_status_label}
            </span>
        );
    }

    return (
        <Badge
            variant="destructive"
            className={`text-nowrap ${className ?? ''}`}
        >
            {work.conflict_status_label}
        </Badge>
    );
}
