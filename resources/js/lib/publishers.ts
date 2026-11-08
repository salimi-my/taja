import { Calendar, Disc3, Flag, Hash, ShieldCheck } from 'lucide-react';
import type { SortColumn } from '@/components/table/table-filter-drawer';

/** The sortable columns of the publishers table, shown in the mobile filter drawer. */
export const PUBLISHER_SORT_COLUMNS: SortColumn[] = [
    { value: 'publisher_code', label: 'Code', icon: Hash },
    { value: 'name', label: 'Name', icon: Disc3 },
    { value: 'society_cmo', label: 'Society', icon: Flag },
    { value: 'status', label: 'Status', icon: ShieldCheck },
    { value: 'agreement_start_date', label: 'Agreement Start', icon: Calendar },
    { value: 'created_at', label: 'Created Date', icon: Calendar },
];

export const PUBLISHER_COLUMN_LABELS = {
    publisher_code: 'Code',
    name: 'Name',
    society_cmo: 'Society',
    status: 'Status',
    agreement_start_date: 'Agreement Start',
    created_at: 'Created Date',
};

/** Turn a list of territory codes into display text, e.g. `MY, SG`. */
export function formatTerritories(territories: string[]): string {
    return territories.join(', ');
}
