export type ImportType = 'works' | 'writers' | 'publishers';

/** A column of the field guide, as sent by `CsvImportController::create`. */
export type ImportColumnGuide = {
    header: string;
    required: boolean;
    format: string;
    example: string;
};

/** An error or warning found in a file. `line` 1 is the header row. */
export type ImportIssue = {
    line: number;
    column: string | null;
    message: string;
};

/** An import as returned by `App\Http\Resources\CsvImportResource`. */
export type CsvImport = {
    id: number;
    type: ImportType;
    type_label: string;
    original_filename: string;
    status:
        | 'validating'
        | 'previewed'
        | 'importing'
        | 'imported'
        | 'cancelled'
        | 'failed';
    status_label: string;
    /** True while a queued job is still working on the file. */
    is_processing: boolean;
    /** Percentage (0-100) of the file the running job has read. */
    progress: number;
    failure_message: string | null;
    /** A problem with the layout of the whole file; while set, it cannot be confirmed. */
    layout_error: string | null;
    has_errors_file: boolean;
    total_rows: number;
    valid_rows: number;
    error_rows: number;
    imported_count: number;
    errors: ImportIssue[];
    errors_total: number;
    warnings: ImportIssue[];
    warnings_total: number;
    uploaded_by: string | null;
    imported_at: string | null;
    created_at: string | null;
};
