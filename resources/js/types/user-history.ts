/** The user account an entry belongs to. Users are deleted for good, so the name and email are copies. */
export type UserHistorySubject = {
    id: number;
    name: string;
    email: string;
};

/** A history entry as returned by `App\Http\Resources\UserHistoryResource`. */
export type UserHistoryEntry = {
    id: number;
    change_type: string;
    change_type_label: string;
    summary: string;
    recorded_at: string;
    actor_name: string | null;
    actor_avatar_url: string | null;
    remarks: string | null;
    subject: UserHistorySubject;
};
