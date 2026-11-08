import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import { TableCell } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { formatShare } from '@/lib/works';
import type { WorkParty } from '@/types';

/** A share such as `50%`, with a muted dash when the party holds none. */
export function ShareCell({ value }: { value: string | null }) {
    return (
        <TableCell className="text-right tabular-nums">
            {value === null ? (
                <span className="text-muted-foreground">-</span>
            ) : (
                formatShare(value)
            )}
        </TableCell>
    );
}

/** The territories of a party as chips, with whether the party is controlled. */
export function PartyTerritoriesCell({
    party,
}: {
    party: Pick<WorkParty, 'territories_controlled' | 'is_controlled'>;
}) {
    return (
        <TableCell>
            <div className="flex flex-wrap gap-1">
                {party.territories_controlled.map((territory) => (
                    <Badge
                        key={territory}
                        variant="outline"
                        className="font-mono"
                    >
                        {territory}
                    </Badge>
                ))}
            </div>
            <div className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                <span
                    className={cn(
                        'size-1.5 rounded-full',
                        party.is_controlled
                            ? 'bg-green-500'
                            : 'bg-muted-foreground/40',
                    )}
                />
                {party.is_controlled ? 'Controlled' : 'Not controlled'}
            </div>
        </TableCell>
    );
}

/** The start date of a party row, with its end date (or "present") below. */
export function PartyPeriodCell({
    party,
}: {
    party: Pick<WorkParty, 'effective_start_date' | 'effective_end_date'>;
}) {
    return (
        <TableCell className="whitespace-nowrap">
            <div>{formatDate(party.effective_start_date)}</div>
            <div className="mt-0.5 text-xs text-muted-foreground">
                to {formatDate(party.effective_end_date) ?? 'present'}
            </div>
        </TableCell>
    );
}
