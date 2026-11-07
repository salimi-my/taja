import { ImportActions } from '@/components/imports/import-actions';
import type { CsvImport } from '@/types';

type ImportActionBarProps = {
    csvImport: CsvImport;
};

function summaryFor(csvImport: CsvImport): string {
    switch (csvImport.status) {
        case 'previewed':
            return `Ready to import ${csvImport.valid_rows.toLocaleString()} of ${csvImport.total_rows.toLocaleString()} rows`;
        case 'validating':
            return 'Checking the file';
        case 'imported':
            return `${csvImport.imported_count.toLocaleString()} rows imported`;
        case 'cancelled':
            return 'Import cancelled';
        default:
            return 'Import failed';
    }
}

/** The import actions, kept in view at the bottom while the page scrolls. */
export function ImportActionBar({ csvImport }: ImportActionBarProps) {
    if (csvImport.status === 'importing') {
        return null;
    }

    return (
        <div className="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sidebar-border bg-card/90 p-3 pl-4 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-card/75 print:hidden">
            <p className="text-sm font-medium">{summaryFor(csvImport)}</p>
            <ImportActions csvImport={csvImport} />
        </div>
    );
}
