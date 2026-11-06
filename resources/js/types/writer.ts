import type { CountryOption, EnumOption } from '@/types/publisher';

/** A pseudonym of a writer. */
export type WriterAlias = {
    id: number;
    name: string;
    ipi_base_number: string | null;
    position: number;
};

/** A writer as returned by `App\Http\Resources\WriterResource`. */
export type Writer = {
    id: number;
    writer_code: string;
    name: string;
    ipi_name_number: string | null;
    cmo_society: string;
    status: string;
    status_label: string;
    default_role: string | null;
    default_role_label: string | null;
    publisher: {
        id: number;
        name: string;
        publisher_code: string;
    } | null;
    aliases: WriterAlias[];
    agreement_start_date: string;
    agreement_end_date: string | null;
    created_at: string | null;
};

/** A writer as returned by `App\Http\Resources\WriterDetailResource`. */
export type WriterDetail = Writer & {
    ipi_base_number: string | null;
    nric: string | null;
    date_of_birth: string | null;
    date_of_death: string | null;
    nationality: string | null;
    publisher_id: number | null;
    controlled_territories: string[];
    rights_types_controlled: string[];
    tin_number: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    auto_renewal: boolean | null;
    pre_term: boolean | null;
    post_term: string | null;
    taja_percentage_deal: string | null;
    writer_percentage_deal: string | null;
    bank_name: string | null;
    bank_account_number: string | null;
    swift_code: string | null;
    bank_address: string | null;
    heir_name: string | null;
    heir_nric: string | null;
    heir_relationship: string | null;
    heir_mobile_number: string | null;
    heir_email: string | null;
    source_system: string | null;
    notes: string | null;
    updated_at: string | null;
};

/** A publisher a writer can be linked to. */
export type WriterPublisherOption = {
    id: number;
    name: string;
    publisher_code: string;
};

/** The option lists the writer filters and form need. */
export type WriterOptions = {
    societies: string[];
    publishers: WriterPublisherOption[];
    countries: CountryOption[];
    roles: EnumOption[];
    rightsTypes: EnumOption[];
    statuses: EnumOption[];
};
