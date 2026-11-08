import type { LucideIcon } from 'lucide-react';
import {
    CalendarClock,
    FilePen,
    FilePlus2,
    FileUp,
    Globe,
    History,
    Percent,
    UserMinus,
    UserPlus,
    Wrench,
} from 'lucide-react';

const GREEN = 'bg-green-500/10 text-green-700 dark:text-green-400';
const BLUE = 'bg-blue-500/10 text-blue-700 dark:text-blue-400';
const AMBER = 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
const RED = 'bg-destructive/10 text-destructive';
const NEUTRAL = 'bg-muted text-muted-foreground';

export type ChangeTypeStyle = { icon: LucideIcon; classes: string };

/** The icon and tint of each change type: additions are green or blue, removals red, fixes amber. */
const CHANGE_TYPE_STYLES: Record<string, ChangeTypeStyle> = {
    work_created: { icon: FilePlus2, classes: GREEN },
    work_updated: { icon: FilePen, classes: NEUTRAL },
    party_added: { icon: UserPlus, classes: BLUE },
    party_removed: { icon: UserMinus, classes: RED },
    share_updated: { icon: Percent, classes: NEUTRAL },
    territory_changed: { icon: Globe, classes: NEUTRAL },
    dates_changed: { icon: CalendarClock, classes: NEUTRAL },
    correction: { icon: Wrench, classes: AMBER },
    imported: { icon: FileUp, classes: GREEN },
};

const FALLBACK_STYLE: ChangeTypeStyle = { icon: History, classes: NEUTRAL };

export function changeTypeStyle(changeType: string): ChangeTypeStyle {
    return CHANGE_TYPE_STYLES[changeType] ?? FALLBACK_STYLE;
}
