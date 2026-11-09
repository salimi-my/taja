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
            className={`w-24 text-nowrap ${tone[work.registration_status] ?? ''} ${className ?? ''}`}
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

type StatusPillProps = {
    label: string;
    value: string;
    /** Colors of the value half, e.g. green for an accepted registration. */
    tone?: string;
};

/** A "label | value" pill, so a status is always read together with what it is. */
function StatusPill({ label, value, tone }: StatusPillProps) {
    return (
        <span className="inline-flex h-5 items-stretch overflow-hidden rounded-full border text-xs font-medium text-nowrap">
            <span className="flex w-[5.75rem] items-center justify-center bg-muted/60 text-muted-foreground">
                {label}
            </span>
            <span
                className={`flex w-[8.5rem] items-center justify-center border-l ${tone ?? ''}`}
            >
                {value}
            </span>
        </span>
    );
}

const REGISTRATION_PILL_TONE: Record<string, string> = {
    accepted: 'bg-green-500/10 text-green-700 dark:text-green-400',
    rejected: 'bg-destructive/10 text-destructive',
    conflict: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
};

type WorkStatusesProps = {
    work: Pick<
        Work,
        | 'registration_status'
        | 'registration_status_label'
        | 'conflict_status'
        | 'conflict_status_label'
    >;
    className?: string;
};

/**
 * The registration status and the conflict status of a work as two labeled
 * pills, so the two are not mistaken for one another.
 */
export function WorkStatuses({ work, className }: WorkStatusesProps) {
    return (
        <div
            className={`flex flex-wrap items-center gap-1.5 ${className ?? ''}`}
        >
            <StatusPill
                label="Registration"
                value={work.registration_status_label}
                tone={REGISTRATION_PILL_TONE[work.registration_status]}
            />
            <StatusPill
                label="Conflict"
                value={work.conflict_status_label}
                tone={
                    work.conflict_status === 'no_conflict'
                        ? 'text-muted-foreground'
                        : 'bg-destructive/10 text-destructive'
                }
            />
        </div>
    );
}
