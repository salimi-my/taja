import { format } from 'date-fns';
import { Calendar, FileText, Flag, Hash, ShieldCheck } from 'lucide-react';
import type { SortColumn } from '@/components/table/table-filter-drawer';

/** The sortable columns of the works table, shown in the mobile filter drawer. */
export const WORK_SORT_COLUMNS: SortColumn[] = [
    { value: 'tap_number', label: 'TAP', icon: Hash },
    { value: 'macp_work_id', label: 'MACP Work ID', icon: Flag },
    { value: 'english_title', label: 'Title', icon: FileText },
    { value: 'registration_status', label: 'Registration', icon: ShieldCheck },
    { value: 'registration_date', label: 'Registration Date', icon: Calendar },
    { value: 'created_at', label: 'Created Date', icon: Calendar },
];

export const WORK_COLUMN_LABELS = {
    tap_number: 'TAP',
    macp_work_id: 'MACP Work ID',
    english_title: 'Title',
    registration_status: 'Registration',
    registration_date: 'Registration Date',
    created_at: 'Created Date',
    registration_statuses: 'Registration status',
    conflict_statuses: 'Conflict status',
    work_types: 'Work type',
    languages: 'Language',
    genres: 'Genre',
    public_domain: 'Public domain',
    controlled: 'Controlled',
};

/** The Yes/No options of the boolean filters. */
export const YES_NO_FILTER_OPTIONS = [
    { id: 'yes', name: 'Yes' },
    { id: 'no', name: 'No' },
];

/** The most ISRCs a work can have. Mirrors the server. */
export const MAX_ISRCS = 50;

/** The most parties a work can be created with. Mirrors the server. */
export const MAX_PARTIES = 50;

/** Format a share such as `25.00` as `25%`, or a dash when it is empty. */
export function formatShare(value: string | null): string {
    return value === null ? '-' : `${Number(value)}%`;
}

/** Today as `YYYY-MM-DD`, the default of the effective date fields. */
export function todayIso(): string {
    return format(new Date(), 'yyyy-MM-dd');
}
