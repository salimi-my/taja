import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Calendar, FileSearch, FileSpreadsheet } from 'lucide-react';
import { memo } from 'react';
import { ImportCellActions } from '@/components/imports/import-cell-actions';
import { ImportStatusBadge } from '@/components/imports/import-status-badge';
import { ImportTypeBadge } from '@/components/imports/import-type-badge';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { UserAvatarName } from '@/components/user-avatar-name';
import { show } from '@/routes/imports';
import type { CsvImportListItem } from '@/types';

type ImportCardRowItemProps = {
    csvImport: CsvImportListItem;
    isSelected: boolean;
    onToggleSelect: (id: number) => void;
};

const ImportCardRowItem = memo(function ImportCardRowItem({
    csvImport,
    isSelected,
    onToggleSelect,
}: ImportCardRowItemProps) {
    return (
        <div className="flex flex-col gap-2 rounded-lg border p-3">
            <div className="flex items-start gap-3">
                <Checkbox
                    aria-label={`Select ${csvImport.original_filename}`}
                    checked={isSelected}
                    onCheckedChange={() => onToggleSelect(csvImport.id)}
                    disabled={!csvImport.can_delete}
                    className="mt-1 disabled:bg-muted-foreground/10"
                />
                <div className="min-w-0 flex-1">
                    <Link
                        href={show(csvImport.id)}
                        className="flex items-center gap-2 text-sm font-semibold"
                    >
                        <FileSpreadsheet className="size-4 shrink-0 text-muted-foreground" />
                        <span className="truncate">
                            {csvImport.original_filename}
                        </span>
                    </Link>
                    {csvImport.uploaded_by && (
                        <UserAvatarName
                            name={csvImport.uploaded_by}
                            avatarUrl={csvImport.uploaded_by_avatar_url}
                            className="mt-1 text-xs text-muted-foreground"
                        />
                    )}
                </div>
                <ImportCellActions csvImport={csvImport} />
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <ImportTypeBadge
                    type={csvImport.type}
                    label={csvImport.type_label}
                    className="px-1.5 text-[10px] leading-none"
                />
                <ImportStatusBadge csvImport={csvImport} />
                <Badge
                    variant="secondary"
                    className="text-[10px] font-normal text-nowrap"
                >
                    {csvImport.total_rows.toLocaleString()} rows
                </Badge>
                <Badge
                    variant="secondary"
                    className="text-[10px] font-normal text-nowrap"
                >
                    {csvImport.valid_rows.toLocaleString()} valid
                </Badge>
                {csvImport.error_rows > 0 && (
                    <Badge
                        variant="destructive"
                        className="text-[10px] font-normal text-nowrap"
                    >
                        {csvImport.error_rows.toLocaleString()} errors
                    </Badge>
                )}
                {csvImport.created_at && (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-normal text-nowrap"
                    >
                        <Calendar className="size-3" />
                        {format(
                            new Date(csvImport.created_at),
                            'dd MMM yyyy, HH:mm',
                        )}
                    </Badge>
                )}
            </div>
        </div>
    );
});

type ImportsCardRowProps = {
    csvImports: CsvImportListItem[];
    selectedIds: Set<number>;
    handleToggleSelect: (id: number) => void;
};

export function ImportsCardRow({
    csvImports,
    selectedIds,
    handleToggleSelect,
}: ImportsCardRowProps) {
    if (csvImports.length === 0) {
        return (
            <Empty className="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <FileSearch />
                    </EmptyMedia>
                    <EmptyTitle>No imports found</EmptyTitle>
                    <EmptyDescription>
                        Try adjusting your search or filters to find what you're
                        looking for.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="space-y-3">
            {csvImports.map((csvImport) => (
                <ImportCardRowItem
                    key={csvImport.id}
                    csvImport={csvImport}
                    isSelected={selectedIds.has(csvImport.id)}
                    onToggleSelect={handleToggleSelect}
                />
            ))}
        </div>
    );
}
