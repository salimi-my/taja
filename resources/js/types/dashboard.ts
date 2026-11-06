export type DashboardStats = {
    works: number;
    writers: number;
    publishers: number;
    incomplete_splits: number;
};

/** The number of works per registration status. */
export type RegistrationBreakdownItem = {
    status: string;
    label: string;
    total: number;
};

/** An import as listed on the dashboard. */
export type RecentImport = {
    id: number;
    type: string;
    type_label: string;
    original_filename: string;
    status: string;
    status_label: string;
    total_rows: number;
    imported_count: number;
    uploaded_by: string | null;
    created_at: string | null;
};
