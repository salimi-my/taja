import { Calendar, Flag, Hash, ShieldCheck, User } from 'lucide-react';
import type { SortColumn } from '@/components/table/table-filter-drawer';

/** The sortable columns of the writers table, shown in the mobile filter drawer. */
export const WRITER_SORT_COLUMNS: SortColumn[] = [
    { value: 'writer_code', label: 'Code', icon: Hash },
    { value: 'name', label: 'Name', icon: User },
    { value: 'cmo_society', label: 'Society', icon: Flag },
    { value: 'status', label: 'Status', icon: ShieldCheck },
    { value: 'agreement_start_date', label: 'Agreement Start', icon: Calendar },
    { value: 'created_at', label: 'Created Date', icon: Calendar },
];

export const WRITER_COLUMN_LABELS = {
    writer_code: 'Code',
    name: 'Name',
    cmo_society: 'Society',
    status: 'Status',
    agreement_start_date: 'Agreement Start',
    created_at: 'Created Date',
    publishers: 'Publisher',
};

/** The most pseudonyms a writer can have. Mirrors the server. */
export const MAX_ALIASES = 4;
