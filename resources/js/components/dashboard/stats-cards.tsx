import { Link } from '@inertiajs/react';
import { Building2, Music, PenLine, TriangleAlert } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { incompleteSplit } from '@/routes/reports';
import { index as publishersIndex } from '@/routes/publishers';
import { index as worksIndex } from '@/routes/works';
import { index as writersIndex } from '@/routes/writers';
import type { DashboardStats } from '@/types';

type StatsCardsProps = {
    stats: DashboardStats;
};

const CARD_CLASS =
    'gap-4 border-sidebar-border transition-colors hover:bg-accent/40 max-md:gap-2 max-md:py-3';

export function StatsCards({ stats }: StatsCardsProps) {
    const needsAttention = stats.incomplete_splits > 0;

    return (
        <div className="grid grid-cols-2 gap-4 max-md:gap-2 xl:grid-cols-4">
            <Link href={worksIndex()}>
                <Card className={CARD_CLASS}>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                        <CardTitle className="text-xs font-medium md:text-sm">
                            Works
                        </CardTitle>
                        <Music className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent className="max-md:px-4">
                        <div className="text-lg font-bold md:text-2xl">
                            {stats.works.toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            In the catalogue
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Link href={writersIndex()}>
                <Card className={CARD_CLASS}>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                        <CardTitle className="text-xs font-medium md:text-sm">
                            Writers
                        </CardTitle>
                        <PenLine className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent className="max-md:px-4">
                        <div className="text-lg font-bold md:text-2xl">
                            {stats.writers.toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Interested parties
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Link href={publishersIndex()}>
                <Card className={CARD_CLASS}>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                        <CardTitle className="text-xs font-medium md:text-sm">
                            Publishers
                        </CardTitle>
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent className="max-md:px-4">
                        <div className="text-lg font-bold md:text-2xl">
                            {stats.publishers.toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Clients
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Link href={incompleteSplit()}>
                <Card className={CARD_CLASS}>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                        <CardTitle className="text-xs font-medium md:text-sm">
                            Incomplete splits
                        </CardTitle>
                        <TriangleAlert
                            className={`h-4 w-4 ${needsAttention ? 'text-amber-600' : 'text-muted-foreground'}`}
                        />
                    </CardHeader>
                    <CardContent className="max-md:px-4">
                        <div
                            className={`text-lg font-bold md:text-2xl ${needsAttention ? 'text-amber-600 dark:text-amber-400' : ''}`}
                        >
                            {stats.incomplete_splits.toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Works that need attention
                        </p>
                    </CardContent>
                </Card>
            </Link>
        </div>
    );
}
