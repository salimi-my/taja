import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { CsvImport } from '@/types';

const tones: Record<CsvImport['status'], string> = {
    validating: 'border-amber-500 text-amber-600 dark:text-amber-400',
    previewed: 'border-sky-500 text-sky-600 dark:text-sky-400',
    importing: 'border-amber-500 text-amber-600 dark:text-amber-400',
    imported: 'border-emerald-500 text-emerald-600 dark:text-emerald-400',
    cancelled: 'text-muted-foreground',
    failed: 'border-destructive text-destructive',
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
