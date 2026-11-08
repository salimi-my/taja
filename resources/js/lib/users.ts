import {
    Calendar,
    CircleCheckBig,
    Mail,
    Phone,
    ShieldCheck,
    User2,
    UsersRound,
} from 'lucide-react';
import type { SortColumn } from '@/components/table/table-filter-drawer';
import type { FilterOption } from '@/types';

/** The verification status filter options for the users table. */
export const STATUS_OPTIONS: FilterOption[] = [
    { id: 'verified', name: 'Verified' },
    { id: 'unverified', name: 'Unverified' },
];

/** The sortable columns of the users table, shown in the mobile filter drawer. */
export const USER_SORT_COLUMNS: SortColumn[] = [
    { value: 'name', label: 'Name', icon: User2 },
    { value: 'email', label: 'Email', icon: Mail },
    { value: 'phone', label: 'Phone', icon: Phone },
    { value: 'gender', label: 'Gender', icon: UsersRound },
    { value: 'role', label: 'Role', icon: ShieldCheck },
    { value: 'email_verified_at', label: 'Verified', icon: CircleCheckBig },
    { value: 'created_at', label: 'Created Date', icon: Calendar },
];

export const USER_COLUMN_LABELS = {
    name: 'Name',
    email: 'Email',
    phone: 'Phone',
    gender: 'Gender',
    role: 'Role',
    email_verified_at: 'Verified',
    created_at: 'Created Date',
};
