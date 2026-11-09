import { ChartPie } from 'lucide-react';
import { RadialBar, RadialBarChart } from 'recharts';
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
import type { RegistrationBreakdownItem } from '@/types';
import { ChartLegend } from './chart-legend';

type RegistrationChartProps = {
    breakdown: RegistrationBreakdownItem[];
};

/** Each ring takes one of the five chart colors, by rank. */
const colorFor = (index: number) => `var(--chart-${(index % 5) + 1})`;

/** How many statuses get their own ring before the rest are grouped as "Others". */
const VISIBLE_STATUSES = 4;

type ChartSlice = { status: string; label: string; total: number };

/**
 * Keep the largest statuses and combine every remaining one into a single "Others" slice.
 * Slices are sorted largest first, so the fullest ring is the innermost.
 */
function topSlices(breakdown: RegistrationBreakdownItem[]): ChartSlice[] {
    const ranked = breakdown
        .filter((item) => item.total > 0)
        .sort((a, b) => b.total - a.total);

    if (ranked.length <= VISIBLE_STATUSES + 1) {
        return ranked;
    }

    const others = ranked.slice(VISIBLE_STATUSES);

    return [
        ...ranked.slice(0, VISIBLE_STATUSES),
        {
            status: 'others',
            label: 'Others',
            total: others.reduce((sum, item) => sum + item.total, 0),
        },
    ].sort((a, b) => b.total - a.total);
}

/** The number of works per registration status. */
export function RegistrationChart({ breakdown }: RegistrationChartProps) {
    const total = breakdown.reduce((sum, item) => sum + item.total, 0);
    const accepted =
        breakdown.find((item) => item.status === 'accepted')?.total ?? 0;

    const slices = topSlices(breakdown);

    const chartConfig: ChartConfig = {
        total: { label: 'Works' },
        ...Object.fromEntries(
            slices.map((slice, index) => [
                slice.status,
                { label: slice.label, color: colorFor(index) },
            ]),
        ),
    };

    const chartData = slices.map((slice) => ({
        status: slice.status,
        total: slice.total,
        fill: `var(--color-${slice.status})`,
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
            <CardContent className="flex flex-1 flex-col gap-4 px-4 md:px-6">
                {slices.length === 0 ? (
                    <Empty className="flex-1 border p-6">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <ChartPie />
                            </EmptyMedia>
                            <EmptyTitle>No works yet</EmptyTitle>
                            <EmptyDescription>
                                Registration statuses appear here once works are
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
                            <RadialBarChart
                                data={chartData}
                                innerRadius="14%"
                                outerRadius="92%"
                            >
                                <ChartTooltip
                                    cursor={false}
                                    content={
                                        <ChartTooltipContent
                                            hideLabel
                                            nameKey="status"
                                        />
                                    }
                                />
                                <RadialBar dataKey="total" background />
                            </RadialBarChart>
                        </ChartContainer>
                        <ChartLegend
                            items={slices.map((slice) => ({
                                key: slice.status,
                                label: slice.label,
                                total: slice.total,
                                color: chartConfig[slice.status]?.color,
                            }))}
                        />
                    </>
                )}
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
