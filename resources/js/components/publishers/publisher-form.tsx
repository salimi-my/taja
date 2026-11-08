import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import PublisherController from '@/actions/App/Http/Controllers/PublisherController';
import {
    CheckboxGroupField,
    DateField,
    PercentageField,
    PhoneField,
    SelectField,
    TerritoryField,
    TextAreaField,
    TextField,
    YES_NO_OPTIONS,
    booleanToSelectValue,
} from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import type { PublisherDetail, PublisherOptions } from '@/types';

type PublisherFormProps = {
    /** The publisher being edited. Leave empty to create a new publisher. */
    publisher?: PublisherDetail;
    options: Pick<PublisherOptions, 'countries' | 'rightsTypes' | 'statuses'>;
    /** Where the cancel button goes back to. */
    cancelHref: InertiaLinkProps['href'];
};

export function PublisherForm({
    publisher,
    options,
    cancelHref,
}: PublisherFormProps) {
    const isEditing = publisher !== undefined;

    return (
        <Form
            {...(isEditing
                ? PublisherController.update.form(publisher.id)
                : PublisherController.store.form())}
            options={{ preserveScroll: true }}
            disableWhileProcessing
            className="flex flex-col gap-6 *:data-[slot=field-set]:border-b *:data-[slot=field-set]:pb-8 *:data-[slot=field-set]:last-of-type:border-b-0 *:data-[slot=field-set]:last-of-type:pb-0"
        >
            {({ processing, errors }) => (
                <>
                    <FieldSet>
                        <FieldLegend>Registration</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="name"
                                label="Publisher name"
                                errors={errors}
                                defaultValue={publisher?.name}
                                placeholder="Registered name"
                            />
                            <TextField
                                name="company_registration_number"
                                label="Company registration number"
                                errors={errors}
                                defaultValue={
                                    publisher?.company_registration_number
                                }
                            />
                            <TextAreaField
                                name="registration_address"
                                label="Registration address"
                                errors={errors}
                                defaultValue={publisher?.registration_address}
                            />
                            <TextField
                                name="official_email"
                                label="Official email"
                                type="email"
                                errors={errors}
                                defaultValue={publisher?.official_email}
                            />
                            <DateField
                                name="incorporation_date"
                                label="Incorporation date"
                                errors={errors}
                                defaultValue={publisher?.incorporation_date}
                            />
                            <TextField
                                name="tin_number"
                                label="TIN number"
                                errors={errors}
                                defaultValue={publisher?.tin_number}
                            />
                            <SelectField
                                name="status"
                                label="Status"
                                errors={errors}
                                options={options.statuses}
                                defaultValue={
                                    publisher?.status ??
                                    options.statuses[0]?.value ??
                                    ''
                                }
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Society and IPI</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="society_cmo"
                                label="Society / CMO"
                                errors={errors}
                                defaultValue={publisher?.society_cmo}
                                placeholder="MACP, WAMI or Non-Member"
                            />
                            <TextField
                                name="society_number"
                                label="Society number"
                                errors={errors}
                                defaultValue={publisher?.society_number}
                            />
                            <TextField
                                name="ipi_base_number"
                                label="IPI base number"
                                errors={errors}
                                defaultValue={publisher?.ipi_base_number}
                            />
                            <TextField
                                name="ipi_name_number"
                                label="IPI name number"
                                errors={errors}
                                defaultValue={publisher?.ipi_name_number}
                                description="11 digits. Used to match this publisher on works."
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Agreement and rights</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <CheckboxGroupField
                                name="rights_types_granted"
                                label="Rights types granted"
                                errors={errors}
                                options={options.rightsTypes}
                                defaultValues={publisher?.rights_types_granted}
                            />
                            <TerritoryField
                                name="territories_granted"
                                label="Territories granted"
                                errors={errors}
                                countries={options.countries}
                                defaultValues={publisher?.territories_granted}
                                description="Pick Worldwide or one or more countries."
                            />
                            <DateField
                                name="agreement_start_date"
                                label="Agreement start date"
                                errors={errors}
                                defaultValue={publisher?.agreement_start_date}
                            />
                            <DateField
                                name="agreement_end_date"
                                label="Agreement end date"
                                errors={errors}
                                defaultValue={publisher?.agreement_end_date}
                            />
                            <SelectField
                                name="auto_renewal"
                                label="Auto renewal"
                                errors={errors}
                                options={YES_NO_OPTIONS}
                                defaultValue={booleanToSelectValue(
                                    publisher?.auto_renewal,
                                )}
                            />
                            <SelectField
                                name="pre_term"
                                label="Pre-term"
                                errors={errors}
                                options={YES_NO_OPTIONS}
                                defaultValue={booleanToSelectValue(
                                    publisher?.pre_term,
                                )}
                            />
                            <TextField
                                name="post_term"
                                label="Post-term"
                                errors={errors}
                                defaultValue={publisher?.post_term}
                                placeholder="e.g. During Active Year"
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Deals</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <PercentageField
                                name="taja_percentage_deal"
                                label="Taja percentage deal"
                                errors={errors}
                                defaultValue={publisher?.taja_percentage_deal}
                            />
                            <PercentageField
                                name="client_percentage_deal"
                                label="Client percentage deal"
                                errors={errors}
                                defaultValue={publisher?.client_percentage_deal}
                                description="When both deals are filled in they must total 100."
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Banking</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="bank_holder_name"
                                label="Account holder name"
                                errors={errors}
                                defaultValue={publisher?.bank_holder_name}
                            />
                            <TextField
                                name="bank_name"
                                label="Bank name"
                                errors={errors}
                                defaultValue={publisher?.bank_name}
                            />
                            <TextField
                                name="client_account_number"
                                label="Account number"
                                errors={errors}
                                defaultValue={publisher?.client_account_number}
                            />
                            <TextField
                                name="swift_code"
                                label="Swift code"
                                errors={errors}
                                defaultValue={publisher?.swift_code}
                            />
                            <TextAreaField
                                name="bank_address"
                                label="Bank address"
                                errors={errors}
                                defaultValue={publisher?.bank_address}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Person in charge</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="person_in_charge_name"
                                label="Name"
                                errors={errors}
                                defaultValue={publisher?.person_in_charge_name}
                            />
                            <TextField
                                name="person_in_charge_title"
                                label="Title"
                                errors={errors}
                                defaultValue={publisher?.person_in_charge_title}
                            />
                            <TextField
                                name="person_in_charge_nric"
                                label="NRIC / passport"
                                errors={errors}
                                defaultValue={publisher?.person_in_charge_nric}
                            />
                            <PhoneField
                                name="person_in_charge_mobile_no"
                                label="Mobile number"
                                errors={errors}
                                defaultValue={
                                    publisher?.person_in_charge_mobile_no
                                }
                            />
                            <TextField
                                name="person_in_charge_email"
                                label="Email"
                                className="sm:col-span-2"
                                type="email"
                                errors={errors}
                                defaultValue={publisher?.person_in_charge_email}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Other</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="source_system"
                                label="Source system"
                                className="sm:col-span-2"
                                errors={errors}
                                defaultValue={publisher?.source_system}
                            />
                            <TextAreaField
                                name="notes"
                                label="Notes"
                                errors={errors}
                                defaultValue={publisher?.notes}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                        <Button disabled={processing} type="submit">
                            {processing && <Spinner className="size-4!" />}
                            {isEditing
                                ? processing
                                    ? 'Saving...'
                                    : 'Save Changes'
                                : processing
                                  ? 'Creating...'
                                  : 'Create'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
