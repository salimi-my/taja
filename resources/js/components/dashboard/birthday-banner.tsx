import { Link } from '@inertiajs/react';
import { Cake, CalendarHeart, PartyPopper, X } from 'lucide-react';
import { useSyncExternalStore } from 'react';
import { Button } from '@/components/ui/button';
import {
    birthdayDate,
    joinNames,
    soonestBirthdays,
    whenLabel,
} from '@/lib/birthdays';
import { cn } from '@/lib/utils';
import { show } from '@/routes/writers';
import type { WriterBirthday } from '@/types';

type BirthdayBannerProps = {
    birthdays: WriterBirthday[];
};

const DISMISSED_PREFIX = 'birthday-banner-dismissed:';

const dismissListeners = new Set<() => void>();

function subscribeToDismissals(onChange: () => void): () => void {
    dismissListeners.add(onChange);
    window.addEventListener('storage', onChange);

    return () => {
        dismissListeners.delete(onChange);
        window.removeEventListener('storage', onChange);
    };
}

function readDismissed(key: string): boolean {
    try {
        return window.localStorage.getItem(DISMISSED_PREFIX + key) === '1';
    } catch {
        return false;
    }
}

function writeDismissed(key: string): void {
    try {
        window.localStorage.setItem(DISMISSED_PREFIX + key, '1');
    } catch {
        // Storage is blocked, so the banner returns on the next visit.
    }

    dismissListeners.forEach((listener) => listener());
}

/**
 * A banner for the soonest birthday: a celebration on the day itself and a
 * softer reminder before it. Closing it hides it for that day on this browser.
 */
export function BirthdayBanner({ birthdays }: BirthdayBannerProps) {
    const group = soonestBirthdays(birthdays);
    const first = group[0];
    const key = first
        ? `${first.date}:${first.days_until === 0 ? 'today' : 'soon'}`
        : '';

    const isDismissed = useSyncExternalStore(
        subscribeToDismissals,
        () => readDismissed(key),
        () => true,
    );

    if (!first || isDismissed) {
        return null;
    }

    const isToday = first.days_until === 0;
    const isSingle = group.length === 1;
    const when = whenLabel(first.days_until);
    const names = joinNames(group.map((item) => item.name));

    const title = isSingle
        ? isToday
            ? `Today is ${first.name}'s birthday`
            : `${first.name}'s birthday is ${when}`
        : `${group.length} writers have a birthday ${when}`;

    const description = isSingle
        ? isToday
            ? `Turning ${first.turning_age} y/o today. Don't forget to send your wishes.`
            : `Turning ${first.turning_age} y/o on ${birthdayDate(first.date).long}.`
        : names;

    const Icon = isToday ? Cake : CalendarHeart;

    return (
        <div
            role="status"
            className={cn(
                'relative overflow-hidden rounded-xl border p-4 motion-safe:animate-in motion-safe:duration-500 motion-safe:fade-in motion-safe:slide-in-from-top-2 max-md:p-3',
                isToday
                    ? 'border-primary/25 bg-linear-to-r from-primary/15 via-primary/5 to-transparent'
                    : 'border-sidebar-border bg-linear-to-r from-accent via-card to-card',
            )}
        >
            <PartyPopper
                aria-hidden="true"
                className={cn(
                    'pointer-events-none absolute -top-3 right-14 size-28 -rotate-12 text-primary max-md:hidden',
                    isToday ? 'opacity-[0.07]' : 'opacity-[0.04]',
                )}
            />

            <div className="relative flex items-center gap-3 max-md:flex-wrap md:gap-4">
                <span
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl max-md:size-9',
                        isToday
                            ? 'bg-primary text-primary-foreground shadow-md shadow-primary/30'
                            : 'bg-primary/10 text-primary',
                    )}
                >
                    <Icon aria-hidden="true" className="size-5 max-md:size-4" />
                </span>

                <div className="min-w-0 flex-1">
                    <p className="text-sm leading-tight font-semibold md:text-base">
                        {title}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground md:text-sm">
                        {description}
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    {isSingle ? (
                        <Button
                            variant={isToday ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link href={show(first.id)} prefetch>
                                View writer
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            variant={isToday ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <a href="#upcoming-birthdays">See birthdays</a>
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Dismiss birthday reminder"
                        onClick={() => writeDismissed(key)}
                    >
                        <X aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
