import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { CsvImport } from '@/types';

const tones: Record<CsvImport['status'], string> = {
    validating:
        'border-transparent bg-amber-500/10 text-amber-600 dark:text-amber-400',
    previewed:
        'border-transparent bg-sky-500/10 text-sky-600 dark:text-sky-400',
    importing:
        'border-transparent bg-amber-500/10 text-amber-600 dark:text-amber-400',
    imported:
        'border-transparent bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    cancelled: 'border-transparent bg-muted text-muted-foreground',
    failed: 'border-transparent bg-destructive/10 text-destructive',
};

type ImportStatusBadgeProps = {
    csvImport: Pick<CsvImport, 'status' | 'status_label' | 'is_processing'>;
    className?: string;
};

/** The status of an import, colored by outcome. */
export function ImportStatusBadge({
    csvImport,
    className,
}: ImportStatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn('text-nowrap', tones[csvImport.status], className)}
        >
            {csvImport.is_processing && <Spinner />}
            {csvImport.status_label}
        </Badge>
    );
}
