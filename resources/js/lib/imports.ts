import {
    Building2,
    Calendar,
    FileText,
    Hash,
    Layers,
    type LucideIcon,
    Music,
    PenLine,
    ShieldCheck,
} from 'lucide-react';
import type { SortColumn } from '@/components/table/table-filter-drawer';
import { index as publishersIndex } from '@/routes/publishers';
import { index as worksIndex } from '@/routes/works';
import { index as writersIndex } from '@/routes/writers';
import type { ImportType } from '@/types';

/** The list page that shows the records an import creates. */
export const importListRoutes: Record<
    ImportType,
    { title: string; href: ReturnType<typeof publishersIndex> }
> = {
    publishers: { title: 'Publishers', href: publishersIndex() },
    writers: { title: 'Writers', href: writersIndex() },
    works: { title: 'Works', href: worksIndex() },
};

/** The order the files have to be imported in, because later files refer to earlier records. */
export const importOrder: ImportType[] = ['publishers', 'writers', 'works'];

/** The import types that can be used today. */
export const availableImports: ImportType[] = [
    'publishers',
    'writers',
    'works',
];

/** The sortable columns of the import history, shown in the mobile filter drawer. */
export const IMPORT_SORT_COLUMNS: SortColumn[] = [
    { value: 'original_filename', label: 'File', icon: FileText },
    { value: 'type', label: 'Type', icon: Layers },
    { value: 'status', label: 'Status', icon: ShieldCheck },
    { value: 'total_rows', label: 'Rows', icon: Hash },
    { value: 'created_at', label: 'Date', icon: Calendar },
];

export const IMPORT_COLUMN_LABELS = {
    original_filename: 'File',
    type: 'Type',
    status: 'Status',
    total_rows: 'Rows',
    created_at: 'Date',
};

/** The icon of each import type, the same as its sidebar menu item. */
export const importTypeIcons: Record<ImportType, LucideIcon> = {
    works: Music,
    writers: PenLine,
    publishers: Building2,
};
