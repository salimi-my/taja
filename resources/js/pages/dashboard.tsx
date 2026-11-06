import { Head, usePage } from '@inertiajs/react';
import { RecentHistory } from '@/components/dashboard/recent-history';
import { RecentImports } from '@/components/dashboard/recent-imports';
import { RegistrationChart } from '@/components/dashboard/registration-chart';
import { StatsCards } from '@/components/dashboard/stats-cards';
import { dashboard } from '@/routes';
import type {
    Auth,
    DashboardStats,
    PaginatedResource,
    RecentImport,
    RegistrationBreakdownItem,
    RightsHistoryEntry,
} from '@/types';

type Props = {
    stats: DashboardStats;
    registrationBreakdown: RegistrationBreakdownItem[];
    recentHistory: Pick<PaginatedResource<RightsHistoryEntry>, 'data'>;
    /** Only sent to admins. */
    recentImports: RecentImport[] | null;
};

function getGreeting(): string {
    const hour = new Date().getHours();

    if (hour >= 6 && hour < 12) {
        return 'Good morning';
    }

    if (hour >= 12 && hour < 18) {
        return 'Good afternoon';
    }

    if (hour >= 18 && hour < 21) {
        return 'Good evening';
    }

    return 'Good night';
}

export default function Dashboard({
    stats,
    registrationBreakdown,
    recentHistory,
    recentImports,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight md:text-2xl">
                        {getGreeting()}, {auth.user.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Here's an overview of the rights catalogue.
                    </p>
                </div>

                <StatsCards stats={stats} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-1">
                        <RegistrationChart breakdown={registrationBreakdown} />
                    </div>
                    <div className="lg:col-span-2">
                        <RecentHistory entries={recentHistory.data} />
                    </div>
                </div>

                {recentImports !== null && (
                    <RecentImports imports={recentImports} />
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
