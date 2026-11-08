import { formatDate } from '@/components/detail-grid';
import { cn } from '@/lib/utils';
import type { RightsSnapshot } from '@/types';

type RightsSnapshotDiffProps = {
    previous: RightsSnapshot | null;
    next: RightsSnapshot | null;
};

type DiffRow = {
    key: string;
    label: string;
    previous: unknown;
    next: unknown;
};

/** Identifiers that are already shown by name, so they only add noise. */
const HIDDEN_KEYS = new Set(['writer_id', 'publisher_id', 'work_party_id']);

const FIELD_LABELS: Record<string, string> = {
    tap_number: 'TAP number',
    macp_work_id: 'MACP work ID',
    iswc: 'ISWC',
    isrcs: 'ISRCs',
    is_controlled: 'Controlled',
    is_public_domain: 'Public domain',
    contributor_name: 'Name',
    territories_controlled: 'Territories controlled',
    territory_exclusions: 'Territory exclusions',
    effective_start_date: 'Effective from',
    effective_end_date: 'Effective until',
    society_no: 'Society no.',
    song_link: 'Song link',
    compass_work_internal_no: 'COMPASS internal no.',
    wami_work_internal_no: 'WAMI internal no.',
};

function labelFor(key: string): string {
    if (FIELD_LABELS[key]) {
        return FIELD_LABELS[key];
    }

    const words = key.replaceAll('_', ' ');

    return words.charAt(0).toUpperCase() + words.slice(1);
}

function isEmpty(value: unknown): boolean {
    return (
        value === null ||
        value === undefined ||
        value === '' ||
        (Array.isArray(value) && value.length === 0)
    );
}

function sameValue(a: unknown, b: unknown): boolean {
    return JSON.stringify(a ?? null) === JSON.stringify(b ?? null);
}

function formatShare(value: unknown): string {
    return `${Number.parseFloat(String(value))}%`;
}

function formatScalar(key: string, value: unknown): string {
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (key.endsWith('_share')) {
        return formatShare(value);
    }

    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return formatDate(value) ?? value;
    }

    return String(value);
}

const TONE_CLASSES = {
    removed: 'bg-destructive/10 text-destructive',
    added: 'bg-green-500/10 text-green-700 dark:text-green-400',
    neutral: 'bg-muted text-foreground',
} as const;

type Tone = keyof typeof TONE_CLASSES;

/**
 * One value of a snapshot. Lists (ISRCs, territories) become chips, and when
 * the other side of a change is given, the items that differ are tinted.
 */
function SnapshotValue({
    field,
    value,
    other,
    tone,
}: {
    field: string;
    value: unknown;
    other?: unknown;
    tone: Tone;
}) {
    if (isEmpty(value)) {
        return <span className="text-muted-foreground">N/A</span>;
    }

    if (Array.isArray(value)) {
        const otherItems = Array.isArray(other) ? other.map(String) : null;

        return (
            <span className="flex flex-wrap gap-1">
                {value.map((item) => {
                    const differs =
                        otherItems !== null &&
                        !otherItems.includes(String(item));

                    return (
                        <span
                            key={String(item)}
                            className={cn(
                                'rounded-sm px-1.5 py-0.5 font-mono text-xs',
                                TONE_CLASSES[differs ? tone : 'neutral'],
                            )}
                        >
                            {String(item)}
                        </span>
                    );
                })}
            </span>
        );
    }

    return (
        <span
            className={cn(
                'inline-block max-w-full rounded-sm px-1.5 py-0.5 break-words',
                TONE_CLASSES[tone],
                tone === 'removed' && 'line-through decoration-destructive/40',
            )}
        >
            {formatScalar(field, value)}
        </span>
    );
}

/** The rows to show: only the fields that changed, or every filled field of a single state. */
export function snapshotDiffRows(
    previous: RightsSnapshot | null,
    next: RightsSnapshot | null,
): DiffRow[] {
    const before = previous ?? {};
    const after = next ?? {};
    const keys = [...new Set([...Object.keys(before), ...Object.keys(after)])];
    const isChange = previous !== null && next !== null;

    return keys
        .filter((key) => !HIDDEN_KEYS.has(key))
        .filter((key) =>
            isChange
                ? !sameValue(before[key], after[key])
                : !isEmpty((previous ?? next)?.[key]),
        )
        .map((key) => ({
            key,
            label: labelFor(key),
            previous: before[key],
            next: after[key],
        }));
}

/**
 * A readable before and after of a history entry. A change lists only the
 * fields that differ; a creation or removal lists every value that was set,
 * with "N/A" on the side where it did not exist.
 */
export function RightsSnapshotDiff({
    previous,
    next,
}: RightsSnapshotDiffProps) {
    const rows = snapshotDiffRows(previous, next);

    if (rows.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No field values were recorded for this entry.
            </p>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border text-sm">
            <div className="hidden grid-cols-[11rem_1fr_1fr] gap-3 border-b bg-muted/50 px-3 py-1.5 text-xs font-medium text-muted-foreground sm:grid">
                <span>Field</span>
                <span>Before</span>
                <span>After</span>
            </div>
            <dl className="divide-y">
                {rows.map((row) => (
                    <div
                        key={row.key}
                        className="grid gap-1 px-3 py-2 sm:grid-cols-[11rem_1fr_1fr] sm:gap-3"
                    >
                        <dt className="text-muted-foreground">{row.label}</dt>
                        <dd className="min-w-0">
                            <span className="mr-2 text-xs text-muted-foreground sm:hidden">
                                Before
                            </span>
                            <SnapshotValue
                                field={row.key}
                                value={row.previous}
                                other={row.next}
                                tone="removed"
                            />
                        </dd>
                        <dd className="min-w-0">
                            <span className="mr-2 text-xs text-muted-foreground sm:hidden">
                                After
                            </span>
                            <SnapshotValue
                                field={row.key}
                                value={row.next}
                                other={row.previous}
                                tone="added"
                            />
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
