import { Link } from '@inertiajs/react';
import { Ellipsis, Eye, FileDown, FileWarning } from 'lucide-react';
import { ImportDelete } from '@/components/imports/import-delete';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { download, errors as errorsFile, show } from '@/routes/imports';
import type { CsvImportListItem } from '@/types';

type ImportCellActionsProps = {
    csvImport: CsvImportListItem;
};

export function ImportCellActions({ csvImport }: ImportCellActionsProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    aria-label="Open menu"
                    variant="ghost"
                    className="flex size-8 p-0 data-[state=open]:bg-muted"
                >
                    <Ellipsis className="size-4" aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                <DropdownMenuItem asChild>
                    <Link
                        href={show(csvImport.id)}
                        className="cursor-pointer text-sm font-medium"
                    >
                        <Eye className="mr-2 size-4" aria-hidden="true" />
                        View
                    </Link>
                </DropdownMenuItem>
                {csvImport.has_original_file && (
                    <DropdownMenuItem asChild>
                        <a
                            href={download.url(csvImport.id)}
                            className="cursor-pointer text-sm font-medium"
                        >
                            <FileDown
                                className="mr-2 size-4"
                                aria-hidden="true"
                            />
                            Download (original)
                        </a>
                    </DropdownMenuItem>
                )}
                {csvImport.has_errors_file && (
                    <DropdownMenuItem asChild>
                        <a
                            href={errorsFile.url(csvImport.id)}
                            className="cursor-pointer text-sm font-medium"
                        >
                            <FileWarning
                                className="mr-2 size-4"
                                aria-hidden="true"
                            />
                            Download (errors)
                        </a>
                    </DropdownMenuItem>
                )}
                {csvImport.can_delete && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <ImportDelete csvImport={csvImport} />
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
