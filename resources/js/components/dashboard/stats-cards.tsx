import { CircleCheckBig, ShieldCheck, UserPlus, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DashboardStats } from '@/types';

type StatsCardsProps = {
    stats: DashboardStats;
};

function toPercentage(value: number, total: number): string {
    return total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
}

export function StatsCards({ stats }: StatsCardsProps) {
    return (
        <div className="grid grid-cols-2 gap-4 max-md:gap-2 xl:grid-cols-4">
            <Card className="gap-4 border-sidebar-border max-md:gap-2 max-md:py-3">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                    <CardTitle className="text-xs font-medium md:text-sm">
                        Total Users
                    </CardTitle>
                    <Users className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent className="max-md:px-4">
                    <div className="text-lg font-bold md:text-2xl">
                        {stats.total_users.toLocaleString()}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Registered in system
                    </p>
                </CardContent>
            </Card>

            <Card className="gap-4 border-sidebar-border max-md:gap-2 max-md:py-3">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                    <CardTitle className="text-xs font-medium md:text-sm">
                        Verified
                    </CardTitle>
                    <CircleCheckBig className="h-4 w-4 text-green-600" />
                </CardHeader>
                <CardContent className="max-md:px-4">
                    <div className="text-lg font-bold md:text-2xl">
                        {stats.verified_users.toLocaleString()}
                    </div>
                    <div className="mt-1 flex items-center gap-2">
                        <Badge
                            variant="secondary"
                            className="text-xs leading-none"
                        >
                            {toPercentage(
                                stats.verified_users,
                                stats.total_users,
                            )}
                            %
                        </Badge>
                        <p className="text-xs text-muted-foreground">
                            of total
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card className="gap-4 border-sidebar-border max-md:gap-2 max-md:py-3">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                    <CardTitle className="text-xs font-medium md:text-sm">
                        Admins
                    </CardTitle>
                    <ShieldCheck className="h-4 w-4 text-orange-600" />
                </CardHeader>
                <CardContent className="max-md:px-4">
                    <div className="text-lg font-bold md:text-2xl">
                        {stats.admin_users.toLocaleString()}
                    </div>
                    <div className="mt-1 flex items-center gap-2">
                        <Badge
                            variant="outline"
                            className="text-xs leading-none"
                        >
                            {toPercentage(stats.admin_users, stats.total_users)}
                            %
                        </Badge>
                        <p className="text-xs text-muted-foreground">
                            of total
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card className="gap-4 border-sidebar-border max-md:gap-2 max-md:py-3">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 max-md:px-4 md:pb-2">
                    <CardTitle className="text-xs font-medium md:text-sm">
                        New Users
                    </CardTitle>
                    <UserPlus className="h-4 w-4 text-yellow-600" />
                </CardHeader>
                <CardContent className="max-md:px-4">
                    <div className="text-lg font-bold md:text-2xl">
                        {stats.new_users.toLocaleString()}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        In the last 30 days
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
