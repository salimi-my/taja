import { Scale } from 'lucide-react';
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
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useIsMobile } from '@/hooks/use-mobile';
import type { ConflictBreakdownItem } from '@/types';
import { ChartLegend } from './chart-legend';
import { ConflictChartSkeleton } from './dashboard-skeletons';

type ConflictChartProps = {
    /** Undefined while the deferred data is still loading. */
    data: ConflictBreakdownItem[] | undefined;
};

/** Give each status a color that fits it: the brand color when clear, rose and amber for conflicts. */
const STATUS_COLORS: Record<string, string> = {
    no_conflict: 'var(--chart-1)',
    share_conflict: 'var(--chart-4)',
    ownership_conflict: 'var(--chart-3)',
    duplicate: 'var(--chart-5)',
    society_query: 'var(--chart-2)',
};

/** Fall back to cycling the five chart colors for a status without its own. */
const colorFor = (value: string, index: number) =>
    STATUS_COLORS[value] ?? `var(--chart-${(index % 5) + 1})`;

/** The number of works per conflict status. */
export function ConflictChart({ data }: ConflictChartProps) {
    const isMobile = useIsMobile();

    if (data === undefined) {
        return <ConflictChartSkeleton />;
    }

    const chartConfig = Object.fromEntries(
        data.map((item, index) => [
            item.value,
            { label: item.label, color: colorFor(item.value, index) },
        ]),
    ) satisfies ChartConfig;

    const chartData = data.map((item) => ({
        status: item.value,
        total: item.total,
        fill: `var(--color-${item.value})`,
    }));

    const total = data.reduce((sum, item) => sum + item.total, 0);

    const inConflict = data
        .filter((item) => item.value !== 'no_conflict')
        .reduce((sum, item) => sum + item.total, 0);

    return (
        <Card className="flex h-full flex-col border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <CardTitle className="max-md:text-sm">
                    Works by conflict status
                </CardTitle>
                <CardDescription className="max-md:text-[11px]">
                    Share, ownership and society queries on the catalogue
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col gap-4 px-4 ps-1.5 md:px-6">
                {total === 0 ? (
                    <Empty className="flex-1 border p-6">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Scale />
                            </EmptyMedia>
                            <EmptyTitle>No works yet</EmptyTitle>
                            <EmptyDescription>
                                Conflict statuses appear here once works are
                                added.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <>
                        <ChartContainer
                            config={chartConfig}
                            className="block aspect-auto h-[240px] w-full min-w-0 max-md:h-[200px] xl:mt-4"
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
                                    width={isMobile ? 90 : 120}
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
                        <ChartLegend
                            items={data.map((item) => ({
                                key: item.value,
                                label: item.label,
                                total: item.total,
                                color: chartConfig[item.value]?.color,
                            }))}
                        />
                    </>
                )}
            </CardContent>
            <CardFooter className="text-sm text-muted-foreground max-md:px-4 max-md:text-xs">
                {inConflict === 0 ? (
                    <span>No works are in conflict</span>
                ) : (
                    <span>
                        <span className="font-semibold text-foreground">
                            {inConflict.toLocaleString()}{' '}
                            {inConflict === 1 ? 'work' : 'works'}
                        </span>{' '}
                        in conflict
                    </span>
                )}
            </CardFooter>
        </Card>
    );
}
