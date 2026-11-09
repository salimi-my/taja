export type ChartLegendItem = {
    key: string;
    label: string;
    total: number;
    color: string | undefined;
};

type ChartLegendProps = {
    items: ChartLegendItem[];
};

/** A color swatch, label and count for each series of a dashboard chart. */
export function ChartLegend({ items }: ChartLegendProps) {
    return (
        <ul className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs">
            {items.map((item) => (
                <li key={item.key} className="flex items-center gap-1.5">
                    <span
                        className="size-2 shrink-0 rounded-[2px]"
                        style={{ backgroundColor: item.color }}
                    />
                    <span className="text-muted-foreground">{item.label}</span>
                    <span className="font-medium text-foreground tabular-nums">
                        {item.total.toLocaleString()}
                    </span>
                </li>
            ))}
        </ul>
    );
}
