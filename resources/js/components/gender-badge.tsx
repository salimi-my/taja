import { GENDER_ICONS } from '@/components/form-fields';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Gender } from '@/types';

type GenderBadgeProps = {
    gender: Gender;
    label: string | null;
    className?: string;
};

/** A gender shown as an outline badge with its symbol. */
export function GenderBadge({ gender, label, className }: GenderBadgeProps) {
    const Icon = GENDER_ICONS[gender];

    return (
        <Badge variant="outline" className={cn('text-nowrap', className)}>
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}
