import { format, parseISO } from 'date-fns';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';
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
import type { SignupPoint } from '@/types';

const chartConfig = {
    signups: {
        label: 'Signups',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

type SignupsChartProps = {
    signups: SignupPoint[];
};

export function SignupsChart({ signups }: SignupsChartProps) {
    const total = signups.reduce((sum, point) => sum + point.signups, 0);
    const average = signups.length > 0 ? total / signups.length : 0;

    return (
        <Card className="flex h-full flex-col border-sidebar-border pt-0">
            <CardHeader className="flex flex-row gap-2 space-y-4 border-b py-5 max-md:p-4 md:items-center md:space-y-0">
                <div className="grid flex-1 gap-1">
                    <CardTitle className="max-md:text-sm">
                        User Signups
                    </CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        New users per day over the last {signups.length} days
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent className="flex-1 px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer
                    config={chartConfig}
                    className="block aspect-auto h-[300px] w-full min-w-0 max-md:h-[200px]"
                >
                    <AreaChart data={signups}>
                        <defs>
                            <linearGradient
                                id="fillSignups"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-signups)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-signups)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={32}
                            interval="preserveStartEnd"
                        />
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    labelFormatter={(_, payload) =>
                                        payload?.[0]?.payload?.date
                                            ? format(
                                                  parseISO(
                                                      payload[0].payload.date,
                                                  ),
                                                  'MMMM dd, yyyy',
                                              )
                                            : ''
                                    }
                                    indicator="dot"
                                />
                            }
                        />
                        <Area
                            dataKey="signups"
                            type="monotone"
                            fill="url(#fillSignups)"
                            stroke="var(--color-signups)"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="flex-col items-start gap-2 text-sm max-md:items-center max-md:px-4 max-md:text-center max-md:text-xs">
                <div className="flex flex-wrap gap-4 leading-none text-muted-foreground">
                    <div>
                        <span className="font-medium text-foreground">
                            {total}
                        </span>{' '}
                        total signups
                    </div>
                    <div>
                        <span className="font-medium text-foreground">
                            {average.toFixed(1)}
                        </span>{' '}
                        per day
                    </div>
                </div>
            </CardFooter>
        </Card>
    );
}
