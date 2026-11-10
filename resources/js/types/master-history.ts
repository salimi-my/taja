import type { RightsSnapshot } from '@/types/work';

/** The writer or publisher an entry belongs to. */
export type MasterHistorySubject = {
    type: 'writer' | 'publisher';
    id: number;
    code: string | null;
    name: string;
    /** `true` once the record is soft-deleted, so there is no page to link to. */
    is_deleted: boolean;
};

/** A history entry as returned by `App\Http\Resources\MasterHistoryResource`. */
export type MasterHistoryEntry = {
    id: number;
    change_type: string;
    change_type_label: string;
    summary: string;
    recorded_at: string;
    user_name: string | null;
    user_avatar_url: string | null;
    csv_import_id: number | null;
    remarks: string | null;
    subject: MasterHistorySubject;
    previous_snapshot: RightsSnapshot | null;
    new_snapshot: RightsSnapshot | null;
};
