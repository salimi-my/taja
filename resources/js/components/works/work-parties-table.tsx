import { Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatTerritories } from '@/lib/publishers';
import { formatShare } from '@/lib/works';
import { show as showPublisher } from '@/routes/publishers';
import { show as showWriter } from '@/routes/writers';
import type { ReactNode } from 'react';
import type { WorkParty } from '@/types';

type WorkPartiesTableProps = {
    parties: WorkParty[];
    emptyDescription?: string;
    /** Adds an actions column with what the callback returns for each row. */
    renderActions?: (party: WorkParty) => ReactNode;
};

/** The writers and publishers on a work with their shares, territories and dates. */
export function WorkPartiesTable({
    parties,
    emptyDescription = 'Parties appear here once they are added to the work.',
    renderActions,
}: WorkPartiesTableProps) {
    if (parties.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Users />
                    </EmptyMedia>
                    <EmptyTitle>No parties</EmptyTitle>
                    <EmptyDescription>{emptyDescription}</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Contributor</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead className="text-right">
                            Performance
                        </TableHead>
                        <TableHead className="text-right">Mechanical</TableHead>
                        <TableHead className="text-right">Sync</TableHead>
                        <TableHead className="text-right">Lyrics</TableHead>
                        <TableHead>Controlled</TableHead>
                        <TableHead>Territories</TableHead>
                        <TableHead>Effective</TableHead>
                        {renderActions && <TableHead>Actions</TableHead>}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {parties.map((party) => (
                        <TableRow key={party.id}>
                            <TableCell>
                                <Link
                                    href={
                                        party.party_type === 'writer'
                                            ? showWriter(party.party_id)
                                            : showPublisher(party.party_id)
                                    }
                                    className="link-hover font-semibold after:h-[2px]"
                                >
                                    {party.contributor_name}
                                </Link>
                                {party.party_code && (
                                    <p className="font-mono text-xs text-muted-foreground">
                                        {party.party_code}
                                    </p>
                                )}
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary">
                                    {party.role_label}
                                </Badge>
                            </TableCell>
                            <TableCell className="text-right">
                                {formatShare(party.performance_share)}
                            </TableCell>
                            <TableCell className="text-right">
                                {formatShare(party.mechanical_share)}
                            </TableCell>
                            <TableCell className="text-right">
                                {formatShare(party.sync_share)}
                            </TableCell>
                            <TableCell className="text-right">
                                {formatShare(party.lyrics_share)}
                            </TableCell>
                            <TableCell>
                                {party.is_controlled ? 'Yes' : 'No'}
                            </TableCell>
                            <TableCell className="max-w-[12rem] whitespace-normal">
                                {formatTerritories(
                                    party.territories_controlled,
                                )}
                                {party.territory_exclusions.length > 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        Excl.{' '}
                                        {formatTerritories(
                                            party.territory_exclusions,
                                        )}
                                    </p>
                                )}
                            </TableCell>
                            <TableCell className="whitespace-nowrap">
                                {formatDate(party.effective_start_date)}
                                {' to '}
                                {formatDate(party.effective_end_date) ??
                                    'present'}
                            </TableCell>
                            {renderActions && (
                                <TableCell>
                                    <div className="flex gap-1">
                                        {renderActions(party)}
                                    </div>
                                </TableCell>
                            )}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
