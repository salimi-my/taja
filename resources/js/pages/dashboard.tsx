import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileUp, History, Plus } from 'lucide-react';
import { useState, useSyncExternalStore } from 'react';
import { ActivityChart } from '@/components/dashboard/activity-chart';
import { AttentionList } from '@/components/dashboard/attention-list';
import { ConflictChart } from '@/components/dashboard/conflict-chart';
import { RecentHistory } from '@/components/dashboard/recent-history';
import { RecentImports } from '@/components/dashboard/recent-imports';
import { RegistrationChart } from '@/components/dashboard/registration-chart';
import { StatsCards } from '@/components/dashboard/stats-cards';
import { TopPublishers } from '@/components/dashboard/top-publishers';
import { Button } from '@/components/ui/button';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { dashboard } from '@/routes';
import { create as importCreate } from '@/routes/imports';
import { rightsHistory } from '@/routes/reports';
import { create as createWork } from '@/routes/works';
import type {
    ActivityPoint,
    Auth,
    AttentionItem,
    ConflictBreakdownItem,
    DashboardStats,
    PaginatedResource,
    RecentImport,
    RegistrationBreakdownItem,
    RightsHistoryEntry,
    TopPublisher,
} from '@/types';

type Props = {
    stats: DashboardStats;
    attention: AttentionItem[];
    registrationBreakdown: RegistrationBreakdownItem[];
    recentHistory: Pick<PaginatedResource<RightsHistoryEntry>, 'data'>;
    /** Only sent to admins. */
    recentImports: RecentImport[] | null;
    activityDays: number;
    /** The charts are deferred, so they are undefined until they have loaded. */
    activity?: ActivityPoint[];
    conflictBreakdown?: ConflictBreakdownItem[];
    topPublishers?: TopPublisher[];
};

function getGreeting(): string {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
        return 'Good morning';
    }

    if (hour >= 12 && hour < 18) {
        return 'Good afternoon';
    }

    if (hour >= 18 && hour < 22) {
        return 'Good evening';
    }

    return 'Good night';
}

const subscribeToNothing = () => () => {};

export default function Dashboard({
    stats,
    attention,
    registrationBreakdown,
    recentHistory,
    recentImports,
    activityDays,
    activity,
    conflictBreakdown,
    topPublishers,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isAdmin = useIsAdmin();
    const greeting = useSyncExternalStore(
        subscribeToNothing,
        getGreeting,
        () => 'Welcome',
    );
    const firstName = auth.user.name.split(' ')[0];

    const [days, setDays] = useState(activityDays);
    const [isReloadingActivity, setIsReloadingActivity] = useState(false);

    /** Reload only the activity chart for the chosen period, keeping the rest of the page as is. */
    function changeDays(next: number) {
        setDays(next);

        router.reload({
            only: ['activity', 'activityDays'],
            data: { days: next },
            onStart: () => setIsReloadingActivity(true),
            onFinish: () => setIsReloadingActivity(false),
        });
    }

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-4 max-md:space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h2 className="truncate text-lg leading-tight font-semibold">
                            {greeting}, {firstName}!
                        </h2>
                        <p className="truncate text-sm text-muted-foreground">
                            Here&apos;s an overview of the rights catalogue.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {isAdmin && (
                            <>
                                <Button asChild>
                                    <Link href={createWork()}>
                                        <Plus aria-hidden="true" />
                                        New work
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={importCreate('works')}>
                                        <FileUp aria-hidden="true" />
                                        Import works
                                    </Link>
                                </Button>
                            </>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={rightsHistory()}>
                                <History aria-hidden="true" />
                                Rights history
                            </Link>
                        </Button>
                    </div>
                </div>

                <StatsCards stats={stats} />

                <div className="grid gap-4 max-md:gap-3 xl:grid-cols-12">
                    <div className="xl:col-span-7">
                        <ActivityChart
                            data={activity}
                            days={days}
                            onDaysChange={changeDays}
                            isReloading={isReloadingActivity}
                        />
                    </div>
                    <div className="xl:col-span-5">
                        <AttentionList items={attention} />
                    </div>
                </div>

                <div className="grid gap-4 max-md:gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <RegistrationChart breakdown={registrationBreakdown} />
                    <ConflictChart data={conflictBreakdown} />
                    <div className="md:col-span-2 xl:col-span-1">
                        <TopPublishers data={topPublishers} />
                    </div>
                </div>

                <div className="grid gap-4 max-md:gap-3 xl:grid-cols-2">
                    <RecentHistory entries={recentHistory.data} />
                    {recentImports !== null && (
                        <RecentImports imports={recentImports} />
                    )}
                </div>
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
