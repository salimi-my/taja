import { Head, usePage } from '@inertiajs/react';
import { RecentUsers } from '@/components/dashboard/recent-users';
import { RoleDistributionChart } from '@/components/dashboard/role-distribution-chart';
import { SignupsChart } from '@/components/dashboard/signups-chart';
import { StatsCards } from '@/components/dashboard/stats-cards';
import { dashboard } from '@/routes';
import type {
    Auth,
    DashboardStats,
    ManagedUser,
    PaginatedResource,
    SignupPoint,
} from '@/types';

type Props = {
    stats: DashboardStats;
    signups: SignupPoint[];
    recentUsers: Pick<PaginatedResource<ManagedUser>, 'data'>;
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

export default function Dashboard({ stats, signups, recentUsers }: Props) {
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
                        Here's an overview of your application.
                    </p>
                </div>

                <StatsCards stats={stats} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <SignupsChart signups={signups} />
                    </div>
                    <RoleDistributionChart stats={stats} />
                </div>

                <RecentUsers
                    users={recentUsers.data}
                    canViewUsers={auth.user.role === 'admin'}
                />
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
