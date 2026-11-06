type DealSplitProps = {
    taja: string | null;
    other: string | null;
    /** Who the other share belongs to, e.g. "Client" or "Writer". */
    otherLabel: string;
};

/** How income is shared, drawn as one bar split between TAJA and the other party. */
export function DealSplit({ taja, other, otherLabel }: DealSplitProps) {
    if (taja === null || other === null) {
        return (
            <p className="text-sm text-muted-foreground">No deal recorded.</p>
        );
    }

    const tajaShare = Number(taja);
    const otherShare = Number(other);

    return (
        <div className="flex flex-col gap-4">
            <div
                role="img"
                aria-label={`Taja ${tajaShare}%, ${otherLabel.toLowerCase()} ${otherShare}%`}
                className="flex h-3 w-full overflow-hidden rounded-full bg-muted"
            >
                <div
                    className="h-full bg-primary"
                    style={{ width: `${tajaShare}%` }}
                />
                <div
                    className="h-full bg-primary/30"
                    style={{ width: `${otherShare}%` }}
                />
            </div>
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <span className="size-2 rounded-full bg-primary" />
                        Taja
                    </div>
                    <div className="text-2xl font-semibold tabular-nums">
                        {tajaShare}%
                    </div>
                </div>
                <div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <span className="size-2 rounded-full bg-primary/30" />
                        {otherLabel}
                    </div>
                    <div className="text-2xl font-semibold tabular-nums">
                        {otherShare}%
                    </div>
                </div>
            </div>
        </div>
    );
}

/** The two shares as short text, e.g. `60% / 40%`, or `-` when there is no deal. */
export function formatDealSplit(
    taja: string | null,
    other: string | null,
): string {
    return taja !== null && other !== null
        ? `${Number(taja)}% / ${Number(other)}%`
        : '-';
}
