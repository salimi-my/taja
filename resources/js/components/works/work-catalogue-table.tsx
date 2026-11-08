import { Link } from '@inertiajs/react';
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
    PartyPeriodCell,
    PartyTerritoriesCell,
    ShareCell,
} from '@/components/works/party-cells';
import { WorkStatuses } from '@/components/works/work-badges';
import { show } from '@/routes/works';
import type { WorkParty } from '@/types';

/**
 * The table of works a writer or publisher is a party of, used by the profile
 * catalogue tabs and the writer catalogue report.
 */
export function WorkCatalogueTable({ rows }: { rows: WorkParty[] }) {
    return (
        <div className="overflow-hidden rounded-md border bg-card">
            <Table>
                <TableHeader className="bg-muted/40">
                    <TableRow className="hover:bg-transparent">
                        <TableHead>Work</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead className="text-right" title="Performance">
                            Perf.
                        </TableHead>
                        <TableHead className="text-right" title="Mechanical">
                            Mech.
                        </TableHead>
                        <TableHead className="text-right">Sync</TableHead>
                        <TableHead className="text-right">Lyrics</TableHead>
                        <TableHead>Territories</TableHead>
                        <TableHead>Effective</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) =>
                        row.work ? (
                            <TableRow key={row.id}>
                                <TableCell className="max-w-[16rem]">
                                    <Link
                                        href={show(row.work.id)}
                                        className="link-hover block truncate font-semibold after:h-[2px]"
                                    >
                                        {row.work.title}
                                    </Link>
                                    <p className="mt-0.5 flex flex-wrap items-center gap-x-2 font-mono text-xs text-muted-foreground">
                                        <span>{row.work.tap_number}</span>
                                        <span aria-hidden>·</span>
                                        <span>{row.work.macp_work_id}</span>
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="secondary"
                                        title={row.role_label}
                                    >
                                        {row.role}
                                    </Badge>
                                </TableCell>
                                <ShareCell value={row.performance_share} />
                                <ShareCell value={row.mechanical_share} />
                                <ShareCell value={row.sync_share} />
                                <ShareCell value={row.lyrics_share} />
                                <PartyTerritoriesCell party={row} />
                                <PartyPeriodCell party={row} />
                                <TableCell>
                                    <WorkStatuses
                                        work={row.work}
                                        className="flex-col items-start"
                                    />
                                </TableCell>
                            </TableRow>
                        ) : null,
                    )}
                </TableBody>
            </Table>
        </div>
    );
}
