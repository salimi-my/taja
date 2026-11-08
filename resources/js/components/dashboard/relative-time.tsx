import { format, formatDistanceToNow, parseISO } from 'date-fns';
import { useSyncExternalStore } from 'react';

const subscribeToNothing = () => () => {};

type RelativeTimeProps = {
    iso: string | null;
    className?: string;
};

/**
 * Show how long ago a timestamp was, such as "3 hours ago". The server renders the plain
 * date instead, since "ago" depends on the visitor's clock and would not match on hydration.
 */
export function RelativeTime({ iso, className }: RelativeTimeProps) {
    const text = useSyncExternalStore(
        subscribeToNothing,
        () =>
            iso
                ? formatDistanceToNow(parseISO(iso), { addSuffix: true })
                : 'N/A',
        () => (iso ? format(parseISO(iso), 'dd MMM yyyy') : 'N/A'),
    );

    return (
        <time dateTime={iso ?? undefined} className={className}>
            {text}
        </time>
    );
}
