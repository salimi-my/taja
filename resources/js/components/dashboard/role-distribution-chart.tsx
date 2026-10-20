import { Pie, PieChart } from 'recharts';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useIsMobile } from '@/hooks/use-mobile';
import type { DashboardStats } from '@/types';

const chartConfig = {
    total: {
        label: 'Users',
    },
    admin: {
        label: 'Admins',
        color: 'var(--chart-1)',
    },
    user: {
        label: 'Users',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

type RoleDistributionChartProps = {
    stats: DashboardStats;
};

export function RoleDistributionChart({ stats }: RoleDistributionChartProps) {
    const isMobile = useIsMobile();
    const regularUsers = stats.total_users - stats.admin_users;

    const data = [
        { role: 'admin', total: stats.admin_users, fill: 'var(--color-admin)' },
        { role: 'user', total: regularUsers, fill: 'var(--color-user)' },
    ];

    return (
        <Card className="flex flex-col border-sidebar-border max-md:gap-3 max-md:py-4">
            <CardHeader className="items-center pb-0 max-md:px-4">
                <CardTitle className="max-md:text-sm">
                    Role Distribution
                </CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    Users grouped by role
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 items-center justify-center pb-0 max-md:px-4">
                <ChartContainer
                    config={chartConfig}
                    className="mx-auto aspect-square h-[120px] w-full max-w-[200px] md:h-[200px]"
                >
                    <PieChart>
                        <ChartTooltip
                            content={
                                <ChartTooltipContent
                                    nameKey="role"
                                    hideLabel
                                    indicator="line"
                                />
                            }
                        />
                        <Pie
                            data={data}
                            dataKey="total"
                            nameKey="role"
                            innerRadius={isMobile ? 40 : 60}
                            outerRadius={isMobile ? 55 : 90}
                        />
                    </PieChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="flex-col gap-2 text-sm max-md:px-4">
                <div className="flex w-full flex-wrap items-center justify-center gap-4 max-md:gap-2">
                    {data.map((item) => (
                        <div
                            key={item.role}
                            className="flex items-center gap-2"
                        >
                            <div
                                className="size-3 rounded-full"
                                style={{ backgroundColor: item.fill }}
                            />
                            <span className="text-muted-foreground">
                                {
                                    chartConfig[item.role as 'admin' | 'user']
                                        .label
                                }
                                :
                            </span>
                            <Badge variant="secondary" className="leading-none">
                                {item.total.toLocaleString()}
                            </Badge>
                        </div>
                    ))}
                </div>
            </CardFooter>
        </Card>
    );
}
