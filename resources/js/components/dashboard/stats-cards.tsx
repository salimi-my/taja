import { Building2, Music, PenLine, TriangleAlert } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { index as publishersIndex } from '@/routes/publishers';
import { incompleteSplit } from '@/routes/reports';
import { index as worksIndex } from '@/routes/works';
import { index as writersIndex } from '@/routes/writers';
import type { DashboardStats } from '@/types';
import { StatCaption, StatCard, TrendBadge } from './stat-card';

type StatsCardsProps = {
    stats: DashboardStats;
};

/** The catalogue counts, each with how it moved against the week before. */
export function StatsCards({ stats }: StatsCardsProps) {
    const needsAttention = stats.incomplete_splits > 0;

    return (
        <div className="grid grid-cols-2 gap-4 max-md:gap-2 xl:grid-cols-4">
            <StatCard
                title="Works"
                value={stats.works}
                icon={Music}
                iconClassName="text-blue-600"
                href={worksIndex()}
            >
                <TrendBadge
                    current={stats.works_this_week}
                    previous={stats.works_last_week}
                />
                <StatCaption>vs previous 7 days</StatCaption>
            </StatCard>

            <StatCard
                title="Writers"
                value={stats.writers}
                icon={PenLine}
                iconClassName="text-violet-600"
                href={writersIndex()}
            >
                <TrendBadge
                    current={stats.writers_this_week}
                    previous={stats.writers_last_week}
                />
                <StatCaption>vs previous 7 days</StatCaption>
            </StatCard>

            <StatCard
                title="Publishers"
                value={stats.publishers}
                icon={Building2}
                iconClassName="text-green-600"
                href={publishersIndex()}
            >
                <TrendBadge
                    current={stats.publishers_this_week}
                    previous={stats.publishers_last_week}
                />
                <StatCaption>vs previous 7 days</StatCaption>
            </StatCard>

            <StatCard
                title="Incomplete splits"
                value={stats.incomplete_splits}
                icon={TriangleAlert}
                iconClassName={
                    needsAttention ? 'text-amber-600' : 'text-green-600'
                }
                valueClassName={
                    needsAttention
                        ? 'text-amber-600 dark:text-amber-400'
                        : undefined
                }
                href={incompleteSplit()}
            >
                {needsAttention ? (
                    <Badge
                        variant="outline"
                        className="border-amber-500 text-xs leading-none text-amber-600 dark:border-amber-400 dark:text-amber-400"
                    >
                        Needs attention
                    </Badge>
                ) : (
                    <Badge variant="secondary" className="text-xs leading-none">
                        All complete
                    </Badge>
                )}
                <StatCaption>works</StatCaption>
            </StatCard>
        </div>
    );
}
