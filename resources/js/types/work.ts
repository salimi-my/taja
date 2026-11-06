import type { CountryOption, EnumOption } from '@/types/publisher';

/** A contributor shown next to a work in the list. */
export type WorkContributor = {
    name: string;
    role: string;
};

/** A work as returned by `App\Http\Resources\WorkResource`. */
export type Work = {
    id: number;
    tap_number: string;
    macp_work_id: string;
    english_title: string | null;
    other_title: string | null;
    title: string;
    iswc: string | null;
    work_type: string | null;
    genre: string | null;
    language: string | null;
    registration_status: string;
    registration_status_label: string;
    conflict_status: string;
    conflict_status_label: string;
    is_public_domain: boolean;
    registration_date: string | null;
    parties_count?: number;
    contributors?: WorkContributor[];
    created_at: string | null;
};

/** A work as returned by `App\Http\Resources\WorkDetailResource`. */
export type WorkDetail = Work & {
    duration: string | null;
    artist_name: string | null;
    original_work_title: string | null;
    sub_title_work: string | null;
    society_no: string | null;
    administration_start_date: string | null;
    administration_end_date: string | null;
    song_link: string | null;
    remark: string | null;
    release_date: string | null;
    compass_work_internal_no: string | null;
    compass_work_type: string | null;
    compass_start_date: string | null;
    wami_work_internal_no: string | null;
    wami_work_type: string | null;
    conflict_notes: string | null;
    isrcs: string[];
    updated_at: string | null;
};

/** The work a party row belongs to, sent with catalogue rows. */
export type WorkPartyWork = {
    id: number;
    tap_number: string;
    macp_work_id: string;
    title: string;
    registration_status: string;
    registration_status_label: string;
    conflict_status: string;
    conflict_status_label: string;
};

/** A party row as returned by `App\Http\Resources\WorkPartyResource`. */
export type WorkParty = {
    id: number;
    work_id: number;
    role: string;
    role_label: string;
    party_type: 'writer' | 'publisher';
    party_id: number;
    party_code: string | null;
    contributor_name: string;
    performance_share: string;
    mechanical_share: string;
    sync_share: string | null;
    lyrics_share: string | null;
    is_controlled: boolean;
    territories_controlled: string[];
    territory_exclusions: string[];
    effective_start_date: string;
    effective_end_date: string | null;
    superseded_at: string | null;
    work?: WorkPartyWork;
};

/** The option lists the work filters need. */
export type WorkFilterOptions = {
    registrationStatuses: EnumOption[];
    conflictStatuses: EnumOption[];
    workTypes: string[];
    languages: string[];
    genres: string[];
};

/** A writer or publisher that can be picked as a party of a work. */
export type WorkPartyOption = {
    id: number;
    name: string;
    code: string;
    ipi_name_number: string | null;
    default_role?: string | null;
};

/** The option lists the party forms need: roles, countries and the party pickers. */
export type WorkPartyOptions = {
    roles: EnumOption[];
    countries: CountryOption[];
    writers: WorkPartyOption[];
    publishers: WorkPartyOption[];
};

/** The option lists the create form needs. */
export type WorkCreateOptions = {
    registrationStatuses: EnumOption[];
    conflictStatuses: EnumOption[];
} & WorkPartyOptions;

/** The option lists the edit form needs. */
export type WorkEditOptions = Pick<
    WorkCreateOptions,
    'registrationStatuses' | 'conflictStatuses'
>;

/** The state of a work or party saved in a history entry. */
export type RightsSnapshot = Record<string, unknown>;

/** A history entry as returned by `App\Http\Resources\RightsHistoryResource`. */
export type RightsHistoryEntry = {
    id: number;
    change_type: string;
    change_type_label: string;
    summary: string;
    effective_date: string;
    recorded_at: string;
    user_name: string | null;
    work_party_id: number | null;
    csv_import_id: number | null;
    remarks: string | null;
    previous_snapshot: RightsSnapshot | null;
    new_snapshot: RightsSnapshot | null;
    /** Only sent by the rights history report. */
    work?: {
        id: number;
        tap_number: string;
        macp_work_id: string;
        title: string;
    };
};
