import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import type { RegistrationBreakdownItem } from '@/types';

const chartConfig = {
    total: {
        label: 'Works',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

type RegistrationChartProps = {
    breakdown: RegistrationBreakdownItem[];
};

/** The number of works per registration status. */
export function RegistrationChart({ breakdown }: RegistrationChartProps) {
    const total = breakdown.reduce((sum, item) => sum + item.total, 0);

    return (
        <Card className="flex h-full flex-col border-sidebar-border max-md:gap-3 max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">
                    Registration status
                </CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    {total.toLocaleString()} works by status with the society
                </CardDescription>
            </CardHeader>
            <CardContent className="flex-1 px-2 sm:px-6">
                <ChartContainer
                    config={chartConfig}
                    className="block aspect-auto h-[260px] w-full min-w-0 max-md:h-[200px]"
                >
                    <BarChart data={breakdown} margin={{ left: -16 }}>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            interval={0}
                            tick={{ fontSize: 11 }}
                        />
                        <YAxis
                            allowDecimals={false}
                            tickLine={false}
                            axisLine={false}
                            width={40}
                        />
                        <ChartTooltip
                            cursor={false}
                            content={<ChartTooltipContent hideLabel />}
                        />
                        <Bar
                            dataKey="total"
                            fill="var(--color-total)"
                            radius={4}
                        />
                    </BarChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
