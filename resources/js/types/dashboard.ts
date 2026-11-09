/** The catalogue counts, with how many were added this week and the week before. */
export type DashboardStats = {
    works: number;
    works_this_week: number;
    works_last_week: number;
    writers: number;
    writers_this_week: number;
    writers_last_week: number;
    publishers: number;
    publishers_this_week: number;
    publishers_last_week: number;
    incomplete_splits: number;
};

export type AttentionKey =
    | 'incomplete_splits'
    | 'works_in_conflict'
    | 'rejected_registrations'
    | 'failed_imports';

/** A check on the catalogue and how many records fail it. */
export type AttentionItem = {
    key: AttentionKey;
    count: number;
    /** The record the check leads to, e.g. the latest failed import. */
    target_id: number | null;
};

/** A writer with a birthday today or in the next few days. */
export type WriterBirthday = {
    id: number;
    name: string;
    writer_code: string | null;
    /** The day of the birthday, as Y-m-d. */
    date: string;
    /** 0 for today, 1 for tomorrow and so on. */
    days_until: number;
    /** The age the writer turns. */
    turning_age: number;
};

/** Works added and rights changes recorded on one day. */
export type ActivityPoint = {
    date: string;
    works: number;
    changes: number;
};

/** The number of works per registration status. */
export type RegistrationBreakdownItem = {
    status: string;
    label: string;
    total: number;
};

/** The number of works per conflict status. */
export type ConflictBreakdownItem = {
    value: string;
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
    uploaded_by_avatar_url: string | null;
    created_at: string | null;
};
