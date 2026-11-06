import { Link } from '@inertiajs/react';
import { formatDate } from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    WorkConflictBadge,
    WorkRegistrationBadge,
} from '@/components/works/work-badges';
import { formatTerritories } from '@/lib/publishers';
import { formatShare } from '@/lib/works';
import { show } from '@/routes/works';
import type { WorkParty } from '@/types';

/**
 * The table of works a writer or publisher is a party of, used by the profile
 * catalogue tabs and the writer catalogue report.
 */
export function WorkCatalogueTable({ rows }: { rows: WorkParty[] }) {
    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>TAP</TableHead>
                        <TableHead>MACP Work ID</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead className="text-right">Perf.</TableHead>
                        <TableHead className="text-right">Mech.</TableHead>
                        <TableHead className="text-right">Sync</TableHead>
                        <TableHead className="text-right">Lyrics</TableHead>
                        <TableHead>Territories</TableHead>
                        <TableHead>Effective</TableHead>
                        <TableHead>Registration</TableHead>
                        <TableHead>Conflict</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) =>
                        row.work ? (
                            <TableRow key={row.id}>
                                <TableCell className="font-mono text-xs">
                                    {row.work.tap_number}
                                </TableCell>
                                <TableCell className="font-mono text-xs">
                                    {row.work.macp_work_id}
                                </TableCell>
                                <TableCell className="max-w-[14rem] truncate">
                                    <Link
                                        href={show(row.work.id)}
                                        className="link-hover font-semibold after:h-[2px]"
                                    >
                                        {row.work.title}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {row.role}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    {formatShare(row.performance_share)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {formatShare(row.mechanical_share)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {formatShare(row.sync_share)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {formatShare(row.lyrics_share)}
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    {formatTerritories(
                                        row.territories_controlled,
                                    )}
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    {formatDate(row.effective_start_date)}
                                    {' to '}
                                    {formatDate(row.effective_end_date) ??
                                        'present'}
                                </TableCell>
                                <TableCell>
                                    <WorkRegistrationBadge work={row.work} />
                                </TableCell>
                                <TableCell>
                                    <WorkConflictBadge work={row.work} />
                                </TableCell>
                            </TableRow>
                        ) : null,
                    )}
                </TableBody>
            </Table>
        </div>
    );
}
