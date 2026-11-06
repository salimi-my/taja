import { format, parseISO } from 'date-fns';
import { TrendingDown, TrendingUp } from 'lucide-react';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { ActivityPoint } from '@/types';
import { ActivityChartSkeleton } from './dashboard-skeletons';

export const ACTIVITY_DAY_OPTIONS = [7, 14, 30, 90] as const;

const chartConfig = {
    works: { label: 'Works added', color: 'var(--chart-1)' },
    changes: { label: 'Rights changes', color: 'var(--chart-2)' },
} satisfies ChartConfig;

type ActivityChartProps = {
    /** Undefined while the deferred data is still loading. */
    data: ActivityPoint[] | undefined;
    days: number;
    onDaysChange: (days: number) => void;
    /** True while a new period is loading, so the old chart is dimmed instead of replaced. */
    isReloading: boolean;
};

const sumOf = (points: ActivityPoint[]) =>
    points.reduce((total, point) => total + point.works + point.changes, 0);

/**
 * Compare the later half of the period with the earlier half, so the footer says
 * whether the catalogue activity is picking up or slowing down.
 */
function describeTrend(points: ActivityPoint[]) {
    const half = Math.floor(points.length / 2);
    const earlier = sumOf(
        points.slice(points.length - half * 2, points.length - half),
    );
    const later = sumOf(points.slice(points.length - half));
    const difference = later - earlier;
    const percentage =
        earlier > 0 ? Math.round((Math.abs(difference) / earlier) * 100) : null;

    return { difference, percentage, half };
}

/** Works added and rights changes recorded per day, for a chosen period. */
export function ActivityChart({
    data,
    days,
    onDaysChange,
    isReloading,
}: ActivityChartProps) {
    if (data === undefined) {
        return <ActivityChartSkeleton />;
    }

    const total = sumOf(data);
    const { difference, percentage, half } = describeTrend(data);

    return (
        <Card className="flex h-full flex-col border-sidebar-border pt-0 max-md:pb-4">
            <CardHeader className="flex flex-row gap-2 space-y-4 border-b py-5 max-md:p-4 md:items-center md:space-y-0">
                <div className="grid flex-1 gap-1">
                    <CardTitle className="max-md:text-sm">Activity</CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        Works added and rights changes recorded per day
                    </CardDescription>
                </div>
                <Select
                    value={String(days)}
                    onValueChange={(value) => onDaysChange(Number(value))}
                >
                    <SelectTrigger
                        className="h-8! w-[125px] rounded-lg px-2 text-xs sm:ml-auto sm:flex md:h-9 md:w-[140px] md:px-3 md:text-sm"
                        aria-label="Select a period"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="rounded-xl max-md:text-xs">
                        {ACTIVITY_DAY_OPTIONS.map((option) => (
                            <SelectItem
                                key={option}
                                value={String(option)}
                                className="rounded-lg"
                            >
                                Last {option} days
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </CardHeader>
            <CardContent className="flex-1 px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer
                    config={chartConfig}
                    className={cn(
                        'block aspect-auto h-[300px] w-full min-w-0 transition-opacity max-md:h-[200px]',
                        isReloading && 'opacity-50',
                    )}
                >
                    <AreaChart data={data}>
                        <defs>
                            <linearGradient
                                id="fillWorks"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-works)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-works)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                            <linearGradient
                                id="fillChanges"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-changes)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-changes)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={32}
                            interval="preserveStartEnd"
                            tickFormatter={(date: string) =>
                                format(parseISO(date), 'MMM dd')
                            }
                        />
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    indicator="dot"
                                    labelFormatter={(date) =>
                                        format(
                                            parseISO(String(date)),
                                            'MMMM dd, yyyy',
                                        )
                                    }
                                />
                            }
                        />
                        <Area
                            dataKey="changes"
                            type="monotone"
                            fill="url(#fillChanges)"
                            stroke="var(--color-changes)"
                        />
                        <Area
                            dataKey="works"
                            type="monotone"
                            fill="url(#fillWorks)"
                            stroke="var(--color-works)"
                        />
                        <ChartLegend content={<ChartLegendContent />} />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="flex-col items-start gap-2 text-sm max-md:items-center max-md:px-4 max-md:text-center max-md:text-xs">
                <div className="flex items-center gap-2 leading-none font-medium">
                    {difference > 0 ? (
                        <>
                            Up {difference.toLocaleString()} on the previous{' '}
                            {half} days
                            {percentage !== null && ` (${percentage}%)`}
                            <TrendingUp className="size-3 text-green-600" />
                        </>
                    ) : difference < 0 ? (
                        <>
                            Down {Math.abs(difference).toLocaleString()} on the
                            previous {half} days
                            {percentage !== null && ` (${percentage}%)`}
                            <TrendingDown className="size-3 text-red-600" />
                        </>
                    ) : (
                        <>No change on the previous {half} days</>
                    )}
                </div>
                <div className="flex flex-wrap gap-4 leading-none text-muted-foreground">
                    <div>
                        <span className="font-medium text-foreground">
                            {total.toLocaleString()}
                        </span>{' '}
                        events
                    </div>
                    <div>
                        <span className="font-medium text-foreground">
                            {(total / data.length).toFixed(1)}
                        </span>{' '}
                        per day
                    </div>
                </div>
            </CardFooter>
        </Card>
    );
}
