import type { WorkParty } from '@/types/work';

/** A writer that can be picked in the writer catalogue report. */
export type ReportWriterOption = {
    id: number;
    name: string;
    code: string;
};

/** The work a report is narrowed to by its `work_id` filter. */
export type ReportWorkFilter = {
    id: number;
    tap_number: string;
    title: string;
};

/** The shares of a work added up per rights type. */
export type ShareTotals = {
    performance: number;
    mechanical: number;
    sync: number | null;
    lyrics: number | null;
};

/** A row of the song ownership split report. */
export type WorkOwnershipRow = {
    id: number;
    tap_number: string;
    macp_work_id: string;
    title: string;
    registration_status: string;
    registration_status_label: string;
    conflict_status: string;
    conflict_status_label: string;
    parties: WorkParty[];
    totals: ShareTotals;
};

/** A row of the incomplete split report. */
export type IncompleteSplitRow = {
    id: number;
    tap_number: string;
    macp_work_id: string;
    title: string;
    registration_status: string;
    registration_status_label: string;
    parties_count: number;
    totals: ShareTotals;
    reasons: string[];
};

/** A user that recorded at least one history entry. */
export type ReportUserOption = {
    id: number;
    name: string;
};
