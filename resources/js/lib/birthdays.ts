import { format, parseISO } from 'date-fns';
import type { WriterBirthday } from '@/types';

/** How far ahead the birthday reminder looks, in days. Keep in sync with `UpcomingBirthdays::LOOKAHEAD_DAYS`. */
export const BIRTHDAY_LOOKAHEAD_DAYS = 3;

/** "today", "tomorrow" or "in 3 days". */
export function whenLabel(daysUntil: number): string {
    if (daysUntil === 0) {
        return 'today';
    }

    if (daysUntil === 1) {
        return 'tomorrow';
    }

    return `in ${daysUntil} days`;
}

/** The day of a birthday split for a calendar tile, e.g. `{ month: 'NOV', day: '12', long: 'Thursday, 12 Nov' }`. */
export function birthdayDate(date: string): {
    month: string;
    day: string;
    long: string;
} {
    const parsed = parseISO(date);

    return {
        month: format(parsed, 'MMM').toUpperCase(),
        day: format(parsed, 'd'),
        long: format(parsed, 'EEEE, d MMM'),
    };
}

/** The birthdays that come first: today's, or else the soonest day's. */
export function soonestBirthdays(
    birthdays: WriterBirthday[],
): WriterBirthday[] {
    if (birthdays.length === 0) {
        return [];
    }

    const soonest = Math.min(...birthdays.map((item) => item.days_until));

    return birthdays.filter((item) => item.days_until === soonest);
}

/** "Ahmad", "Ahmad and Siti" or "Ahmad, Siti and 2 others". */
export function joinNames(names: string[]): string {
    if (names.length <= 2) {
        return names.join(' and ');
    }

    const others = names.length - 2;

    return `${names.slice(0, 2).join(', ')} and ${others} ${others === 1 ? 'other' : 'others'}`;
}
