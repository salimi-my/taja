import { Link } from '@inertiajs/react';
import { Cake, PartyPopper } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    BIRTHDAY_LOOKAHEAD_DAYS,
    birthdayDate,
    whenLabel,
} from '@/lib/birthdays';
import { cn } from '@/lib/utils';
import { show } from '@/routes/writers';
import type { WriterBirthday } from '@/types';

type UpcomingBirthdaysProps = {
    birthdays: WriterBirthday[];
};

/** Writers with a birthday today or in the next few days, today's first. */
export function UpcomingBirthdays({ birthdays }: UpcomingBirthdaysProps) {
    const todayCount = birthdays.filter((item) => item.days_until === 0).length;

    return (
        <Card
            id="upcoming-birthdays"
            className="h-full scroll-mt-20 border-sidebar-border max-md:py-4"
        >
            <CardHeader className="flex flex-row items-start justify-between gap-2 max-md:px-4">
                <div className="grid gap-1">
                    <CardTitle className="max-md:text-sm">
                        Upcoming birthdays
                    </CardTitle>
                    <CardDescription className="max-md:text-[11px]">
                        Writers celebrating in the next{' '}
                        {BIRTHDAY_LOOKAHEAD_DAYS} days
                    </CardDescription>
                </div>
                {birthdays.length > 0 && (
                    <Badge
                        variant={todayCount > 0 ? 'default' : 'secondary'}
                        className="shrink-0 gap-1"
                    >
                        <Cake aria-hidden="true" className="size-3" />
                        {todayCount > 0
                            ? `${todayCount} today`
                            : `${birthdays.length} soon`}
                    </Badge>
                )}
            </CardHeader>
            <CardContent className="flex flex-1 flex-col max-md:px-4">
                {birthdays.length === 0 ? (
                    <Empty className="border p-6">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <PartyPopper />
                            </EmptyMedia>
                            <EmptyTitle>No birthdays coming up</EmptyTitle>
                            <EmptyDescription>
                                Writers with a birthday in the next{' '}
                                {BIRTHDAY_LOOKAHEAD_DAYS} days will show here.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <div className="relative min-h-72 flex-1">
                        <ul className="absolute inset-0 no-scrollbar space-y-2.5 overflow-y-auto">
                            {birthdays.map((item) => {
                                const isToday = item.days_until === 0;
                                const date = birthdayDate(item.date);

                                return (
                                    <li key={item.id}>
                                        <Link
                                            href={show(item.id)}
                                            prefetch
                                            className={cn(
                                                'flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent/50',
                                                isToday &&
                                                    'border-primary/30 bg-primary/5 hover:bg-primary/10',
                                            )}
                                        >
                                            <span
                                                aria-hidden="true"
                                                className={cn(
                                                    'flex size-11 shrink-0 flex-col items-center justify-center rounded-lg border bg-card leading-none',
                                                    isToday &&
                                                        'border-transparent bg-primary text-primary-foreground shadow-sm shadow-primary/30',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'text-[10px] font-semibold tracking-wide text-primary pt-1',
                                                        isToday &&
                                                            'text-primary-foreground/80',
                                                    )}
                                                >
                                                    {date.month}
                                                </span>
                                                <span className="text-base font-semibold tabular-nums">
                                                    {date.day}
                                                </span>
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm leading-tight font-medium">
                                                    {item.name}
                                                </p>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    Turning{' '}
                                                    <span className="font-medium text-foreground">
                                                        {item.turning_age} y/o
                                                    </span>
                                                    {item.writer_code &&
                                                        ` · ${item.writer_code}`}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    isToday
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                className="shrink-0 px-2 text-[11px] font-medium"
                                            >
                                                {isToday && (
                                                    <Cake
                                                        aria-hidden="true"
                                                        className="size-3"
                                                    />
                                                )}
                                                <span className="first-letter:uppercase">
                                                    {whenLabel(item.days_until)}
                                                </span>
                                            </Badge>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
