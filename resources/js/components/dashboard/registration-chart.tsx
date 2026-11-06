import { Bar, BarChart, LabelList, XAxis, YAxis } from 'recharts';
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
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { useIsMobile } from '@/hooks/use-mobile';
import type { RegistrationBreakdownItem } from '@/types';

type RegistrationChartProps = {
    breakdown: RegistrationBreakdownItem[];
};

/** Give each status a color that fits it: green accepted, rose rejected, amber conflict. */
const STATUS_COLORS: Record<string, string> = {
    draft: 'var(--muted-foreground)',
    submitted: 'var(--chart-2)',
    accepted: 'var(--chart-5)',
    rejected: 'var(--chart-4)',
    conflict: 'var(--chart-3)',
    amended: 'var(--chart-1)',
    relinquished:
        'color-mix(in oklab, var(--chart-4) 40%, var(--muted-foreground))',
};

/** Fall back to cycling the five chart colors for a status without its own. */
const colorFor = (status: string, index: number) =>
    STATUS_COLORS[status] ?? `var(--chart-${(index % 5) + 1})`;

/** The number of works per registration status. */
export function RegistrationChart({ breakdown }: RegistrationChartProps) {
    const isMobile = useIsMobile();
    const total = breakdown.reduce((sum, item) => sum + item.total, 0);
    const accepted =
        breakdown.find((item) => item.status === 'accepted')?.total ?? 0;

    const chartConfig = Object.fromEntries(
        breakdown.map((item, index) => [
            item.status,
            { label: item.label, color: colorFor(item.status, index) },
        ]),
    ) satisfies ChartConfig;

    const chartData = breakdown.map((item) => ({
        status: item.status,
        total: item.total,
        fill: `var(--color-${item.status})`,
    }));

    return (
        <Card className="flex h-full flex-col border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">
                    Works by registration status
                </CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    Where each work stands with the society
                </CardDescription>
            </CardHeader>
            <CardContent className="flex-1 px-4 ps-1.5 md:px-6">
                <ChartContainer
                    config={chartConfig}
                    className="block aspect-auto h-[260px] w-full min-w-0 max-md:h-[220px]"
                >
                    <BarChart
                        accessibilityLayer
                        data={chartData}
                        layout="vertical"
                        margin={{ left: 0, right: 32 }}
                    >
                        <YAxis
                            dataKey="status"
                            type="category"
                            tick={{ fontSize: isMobile ? 10 : 12 }}
                            tickLine={false}
                            tickMargin={10}
                            axisLine={false}
                            width={isMobile ? 78 : 96}
                            tickFormatter={(value: string) =>
                                chartConfig[value]?.label ?? value
                            }
                        />
                        <XAxis dataKey="total" type="number" hide />
                        <ChartTooltip
                            cursor={false}
                            content={<ChartTooltipContent hideLabel />}
                        />
                        <Bar dataKey="total" radius={5}>
                            <LabelList
                                dataKey="total"
                                position="right"
                                offset={8}
                                className="fill-foreground"
                                fontSize={12}
                            />
                        </Bar>
                    </BarChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="text-sm text-muted-foreground max-md:px-4 max-md:text-xs">
                {total === 0 ? (
                    <span>No works yet</span>
                ) : (
                    <span>
                        <span className="font-semibold text-foreground">
                            {total.toLocaleString()}{' '}
                            {total === 1 ? 'work' : 'works'}
                        </span>{' '}
                        in total, {accepted.toLocaleString()} accepted
                    </span>
                )}
            </CardFooter>
        </Card>
    );
}
