/** A publisher as returned by `App\Http\Resources\PublisherResource`. */
export type Publisher = {
    id: number;
    publisher_code: string;
    name: string;
    ipi_name_number: string | null;
    society_cmo: string;
    status: string;
    status_label: string;
    rights_types_granted: string[];
    territories_granted: string[];
    agreement_start_date: string;
    agreement_end_date: string | null;
    created_at: string | null;
};

/** A publisher as returned by `App\Http\Resources\PublisherDetailResource`. */
export type PublisherDetail = Publisher & {
    ipi_base_number: string | null;
    company_registration_number: string;
    registration_address: string;
    official_email: string;
    incorporation_date: string | null;
    society_number: string | null;
    tin_number: string | null;
    auto_renewal: boolean | null;
    pre_term: boolean | null;
    post_term: string | null;
    bank_holder_name: string | null;
    bank_name: string | null;
    client_account_number: string | null;
    swift_code: string | null;
    bank_address: string | null;
    person_in_charge_name: string | null;
    person_in_charge_title: string | null;
    person_in_charge_nric: string | null;
    person_in_charge_mobile_no: string | null;
    person_in_charge_email: string | null;
    taja_percentage_deal: string | null;
    client_percentage_deal: string | null;
    source_system: string | null;
    notes: string | null;
    updated_at: string | null;
};

/** A value/label pair as returned by an enum's `options()` method. */
export type EnumOption = {
    value: string;
    label: string;
};

/** The option lists the publisher filters and form need. */
/** A country a writer or publisher can hold rights in. */
export type CountryOption = {
    code: string;
    name: string;
};

export type PublisherOptions = {
    societies: string[];
    territories: string[];
    countries: CountryOption[];
    rightsTypes: EnumOption[];
    statuses: EnumOption[];
};
