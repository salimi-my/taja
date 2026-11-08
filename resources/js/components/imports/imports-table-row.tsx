import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { FileSearch, FileSpreadsheet } from 'lucide-react';
import { memo } from 'react';
import { ImportCellActions } from '@/components/imports/import-cell-actions';
import { ImportStatusBadge } from '@/components/imports/import-status-badge';
import { ImportTypeBadge } from '@/components/imports/import-type-badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TableCell, TableRow } from '@/components/ui/table';
import { UserAvatarName } from '@/components/user-avatar-name';
import { cn } from '@/lib/utils';
import { show } from '@/routes/imports';
import type { CsvImportListItem } from '@/types';

type ImportTableRowItemProps = {
    csvImport: CsvImportListItem;
    isSelected: boolean;
    onToggleSelect: (id: number) => void;
};

const ImportTableRowItem = memo(function ImportTableRowItem({
    csvImport,
    isSelected,
    onToggleSelect,
}: ImportTableRowItemProps) {
    return (
        <TableRow>
            <TableCell>
                <Checkbox
                    aria-label={`Select ${csvImport.original_filename}`}
                    checked={isSelected}
                    onCheckedChange={() => onToggleSelect(csvImport.id)}
                    disabled={!csvImport.can_delete}
                />
            </TableCell>
            <TableCell>
                <Link
                    href={show(csvImport.id)}
                    className="link-hover inline-flex items-center gap-2 font-semibold after:h-[2px]"
                >
                    <FileSpreadsheet className="size-4 shrink-0 text-muted-foreground" />
                    {csvImport.original_filename}
                </Link>
            </TableCell>
            <TableCell>
                <ImportTypeBadge
                    type={csvImport.type}
                    label={csvImport.type_label}
                />
            </TableCell>
            <TableCell>
                <ImportStatusBadge csvImport={csvImport} />
            </TableCell>
            <TableCell className="tabular-nums">
                {csvImport.total_rows.toLocaleString()}
            </TableCell>
            <TableCell className="tabular-nums">
                {csvImport.valid_rows.toLocaleString()}
            </TableCell>
            <TableCell>
                <span
                    className={cn(
                        'inline-flex items-center gap-1 tabular-nums',
                        csvImport.error_rows > 0 && 'text-destructive',
                    )}
                >
                    {csvImport.error_rows.toLocaleString()}
                </span>
            </TableCell>
            <TableCell>
                {csvImport.uploaded_by ? (
                    <UserAvatarName
                        name={csvImport.uploaded_by}
                        avatarUrl={csvImport.uploaded_by_avatar_url}
                    />
                ) : (
                    'N/A'
                )}
            </TableCell>
            <TableCell className="text-nowrap">
                {csvImport.created_at
                    ? format(
                          new Date(csvImport.created_at),
                          'dd MMM yyyy, HH:mm',
                      )
                    : 'N/A'}
            </TableCell>
            <TableCell>
                <ImportCellActions csvImport={csvImport} />
            </TableCell>
        </TableRow>
    );
});

type ImportsTableRowProps = {
    csvImports: CsvImportListItem[];
    selectedIds: Set<number>;
    handleToggleSelect: (id: number) => void;
};

export function ImportsTableRow({
    csvImports,
    selectedIds,
    handleToggleSelect,
}: ImportsTableRowProps) {
    if (csvImports.length === 0) {
        return (
            <TableRow className="hover:bg-transparent">
                <TableCell colSpan={999}>
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileSearch />
                            </EmptyMedia>
                            <EmptyTitle>No imports found</EmptyTitle>
                            <EmptyDescription>
                                Try adjusting your search or filters, or upload
                                a file from the Import page of a catalogue
                                section.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </TableCell>
            </TableRow>
        );
    }

    return (
        <>
            {csvImports.map((csvImport) => (
                <ImportTableRowItem
                    key={csvImport.id}
                    csvImport={csvImport}
                    isSelected={selectedIds.has(csvImport.id)}
                    onToggleSelect={handleToggleSelect}
                />
            ))}
        </>
    );
}
