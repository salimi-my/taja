import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import WriterController from '@/actions/App/Http/Controllers/WriterController';
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
import type { Errors } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { MAX_ALIASES } from '@/lib/writers';
import type { WriterDetail, WriterOptions } from '@/types';

type WriterFormProps = {
    /** The writer being edited. Leave empty to create a new writer. */
    writer?: WriterDetail;
    options: Pick<
        WriterOptions,
        'publishers' | 'countries' | 'roles' | 'rightsTypes' | 'statuses'
    >;
    /** Where the cancel button goes back to. */
    cancelHref: InertiaLinkProps['href'];
};

type AliasRow = {
    /** A stable key so typed values stay with their row when another is removed. */
    key: number;
    name: string;
    ipi_base_number: string;
};

type AliasRepeaterProps = {
    initialAliases: WriterDetail['aliases'];
    errors: Errors;
};

/** Up to four pseudonyms, each with a name and an optional IPI number. */
function AliasRepeater({ initialAliases, errors }: AliasRepeaterProps) {
    const [nextKey, setNextKey] = useState(initialAliases.length);
    const [rows, setRows] = useState<AliasRow[]>(
        initialAliases.map((alias, index) => ({
            key: index,
            name: alias.name,
            ipi_base_number: alias.ipi_base_number ?? '',
        })),
    );

    const addRow = () => {
        setRows((current) => [
            ...current,
            { key: nextKey, name: '', ipi_base_number: '' },
        ]);
        setNextKey((current) => current + 1);
    };

    return (
        <FieldSet>
            <FieldLegend>Pseudonyms</FieldLegend>
            <FieldGroup className="flex flex-col gap-4 [&>[data-slot=field]]:gap-2">
                {rows.map((row, index) => (
                    <div
                        key={row.key}
                        className="flex items-start gap-2 rounded-lg border p-3"
                    >
                        <div className="grid flex-1 gap-3 sm:grid-cols-2">
                            <Field
                                data-invalid={!!errors[`aliases.${index}.name`]}
                            >
                                <FieldLabel htmlFor={`alias-name-${row.key}`}>
                                    Pseudonym / stage name
                                </FieldLabel>
                                <Input
                                    id={`alias-name-${row.key}`}
                                    name={`aliases[${index}][name]`}
                                    defaultValue={row.name}
                                    autoComplete="off"
                                    aria-invalid={
                                        !!errors[`aliases.${index}.name`]
                                    }
                                />
                                <FieldError>
                                    {errors[`aliases.${index}.name`]}
                                </FieldError>
                            </Field>
                            <Field
                                data-invalid={
                                    !!errors[`aliases.${index}.ipi_base_number`]
                                }
                            >
                                <FieldLabel htmlFor={`alias-ipi-${row.key}`}>
                                    IPI number
                                </FieldLabel>
                                <Input
                                    id={`alias-ipi-${row.key}`}
                                    name={`aliases[${index}][ipi_base_number]`}
                                    defaultValue={row.ipi_base_number}
                                    autoComplete="off"
                                />
                                <FieldError>
                                    {errors[`aliases.${index}.ipi_base_number`]}
                                </FieldError>
                            </Field>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Remove pseudonym"
                            onClick={() =>
                                setRows((current) =>
                                    current.filter(
                                        (item) => item.key !== row.key,
                                    ),
                                )
                            }
                        >
                            <X />
                        </Button>
                    </div>
                ))}
                <FieldError>{errors.aliases}</FieldError>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={rows.length >= MAX_ALIASES}
                        onClick={addRow}
                    >
                        <Plus />
                        Add pseudonym
                    </Button>
                </div>
            </FieldGroup>
        </FieldSet>
    );
}

export function WriterForm({ writer, options, cancelHref }: WriterFormProps) {
    const isEditing = writer !== undefined;

    const roleOptions = [
        { value: 'unset', label: 'Not set' },
        ...options.roles,
    ];
    const publisherOptions = [
        { value: 'unset', label: 'None' },
        ...options.publishers.map((publisher) => ({
            value: String(publisher.id),
            label: `${publisher.name} (${publisher.publisher_code})`,
        })),
    ];

    return (
        <Form
            {...(isEditing
                ? WriterController.update.form(writer.id)
                : WriterController.store.form())}
            options={{ preserveScroll: true }}
            disableWhileProcessing
            className="flex flex-col gap-6 *:data-[slot=field-set]:border-b *:data-[slot=field-set]:pb-8 *:data-[slot=field-set]:last-of-type:border-b-0 *:data-[slot=field-set]:last-of-type:pb-0"
        >
            {({ processing, errors }) => (
                <>
                    <FieldSet>
                        <FieldLegend>Identity</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="name"
                                label="Name"
                                errors={errors}
                                defaultValue={writer?.name}
                                placeholder="Preferred display name"
                            />
                            <SelectField
                                name="default_role"
                                label="Default role"
                                errors={errors}
                                options={roleOptions}
                                defaultValue={writer?.default_role ?? 'unset'}
                            />
                            <SelectField
                                name="status"
                                label="Status"
                                errors={errors}
                                options={options.statuses}
                                defaultValue={
                                    writer?.status ??
                                    options.statuses[0]?.value ??
                                    ''
                                }
                            />
                            <TextField
                                name="nric"
                                label="NRIC / passport"
                                errors={errors}
                                defaultValue={writer?.nric}
                            />
                            <DateField
                                name="date_of_birth"
                                label="Date of birth"
                                errors={errors}
                                defaultValue={writer?.date_of_birth}
                            />
                            <DateField
                                name="date_of_death"
                                label="Date of death"
                                errors={errors}
                                defaultValue={writer?.date_of_death}
                            />
                            <TextField
                                name="nationality"
                                label="Nationality"
                                errors={errors}
                                defaultValue={writer?.nationality}
                            />
                            <TextField
                                name="tin_number"
                                label="TIN number"
                                errors={errors}
                                defaultValue={writer?.tin_number}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Contact</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="email"
                                label="Email"
                                type="email"
                                errors={errors}
                                defaultValue={writer?.email}
                            />
                            <PhoneField
                                name="phone"
                                label="Phone"
                                errors={errors}
                                defaultValue={writer?.phone}
                            />
                            <TextAreaField
                                name="address"
                                label="Address"
                                errors={errors}
                                defaultValue={writer?.address}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Society and IPI</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="cmo_society"
                                label="CMO / society"
                                errors={errors}
                                defaultValue={writer?.cmo_society}
                                placeholder="MACP, WAMI or Non-Member"
                            />
                            <TextField
                                name="ipi_base_number"
                                label="IPI base number"
                                errors={errors}
                                defaultValue={writer?.ipi_base_number}
                            />
                            <TextField
                                name="ipi_name_number"
                                label="IPI name number"
                                className="sm:col-span-2"
                                errors={errors}
                                defaultValue={writer?.ipi_name_number}
                                description="11 digits. Used to match this writer on works."
                            />
                        </FieldGroup>
                    </FieldSet>

                    <AliasRepeater
                        initialAliases={writer?.aliases ?? []}
                        errors={errors}
                    />

                    <FieldSet>
                        <FieldLegend>Agreement and rights</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <SelectField
                                name="publisher_id"
                                label="Publisher"
                                errors={errors}
                                options={publisherOptions}
                                display="combobox"
                                className="sm:col-span-2"
                                defaultValue={
                                    writer?.publisher_id
                                        ? String(writer.publisher_id)
                                        : 'unset'
                                }
                            />
                            <CheckboxGroupField
                                name="rights_types_controlled"
                                label="Rights types controlled"
                                errors={errors}
                                options={options.rightsTypes}
                                defaultValues={writer?.rights_types_controlled}
                            />
                            <TerritoryField
                                name="controlled_territories"
                                label="Controlled territories"
                                errors={errors}
                                countries={options.countries}
                                defaultValues={writer?.controlled_territories}
                                description="Pick Worldwide or one or more countries."
                            />
                            <DateField
                                name="agreement_start_date"
                                label="Agreement start date"
                                errors={errors}
                                defaultValue={writer?.agreement_start_date}
                            />
                            <DateField
                                name="agreement_end_date"
                                label="Agreement end date"
                                errors={errors}
                                defaultValue={writer?.agreement_end_date}
                            />
                            <SelectField
                                name="auto_renewal"
                                label="Auto renewal"
                                errors={errors}
                                options={YES_NO_OPTIONS}
                                defaultValue={booleanToSelectValue(
                                    writer?.auto_renewal,
                                )}
                            />
                            <SelectField
                                name="pre_term"
                                label="Pre-term"
                                errors={errors}
                                options={YES_NO_OPTIONS}
                                defaultValue={booleanToSelectValue(
                                    writer?.pre_term,
                                )}
                            />
                            <TextField
                                name="post_term"
                                label="Post-term"
                                errors={errors}
                                defaultValue={writer?.post_term}
                                placeholder="e.g. During Active Year"
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Deal</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <PercentageField
                                name="taja_percentage_deal"
                                label="Taja percentage"
                                errors={errors}
                                defaultValue={writer?.taja_percentage_deal}
                                description="0 to 100. Both percentages must total 100."
                            />
                            <PercentageField
                                name="writer_percentage_deal"
                                label="Writer percentage"
                                errors={errors}
                                defaultValue={writer?.writer_percentage_deal}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Banking</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="bank_name"
                                label="Bank name"
                                errors={errors}
                                defaultValue={writer?.bank_name}
                            />
                            <TextField
                                name="bank_account_number"
                                label="Account number"
                                errors={errors}
                                defaultValue={writer?.bank_account_number}
                            />
                            <TextField
                                name="swift_code"
                                label="Swift code"
                                className="sm:col-span-2"
                                errors={errors}
                                defaultValue={writer?.swift_code}
                            />
                            <TextAreaField
                                name="bank_address"
                                label="Bank address"
                                errors={errors}
                                defaultValue={writer?.bank_address}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Heir</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="heir_name"
                                label="Name"
                                errors={errors}
                                defaultValue={writer?.heir_name}
                            />
                            <TextField
                                name="heir_nric"
                                label="NRIC / passport"
                                errors={errors}
                                defaultValue={writer?.heir_nric}
                            />
                            <TextField
                                name="heir_relationship"
                                label="Relationship"
                                errors={errors}
                                defaultValue={writer?.heir_relationship}
                            />
                            <PhoneField
                                name="heir_mobile_number"
                                label="Mobile number"
                                errors={errors}
                                defaultValue={writer?.heir_mobile_number}
                            />
                            <TextField
                                name="heir_email"
                                label="Email"
                                className="sm:col-span-2"
                                type="email"
                                errors={errors}
                                defaultValue={writer?.heir_email}
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
                                defaultValue={writer?.source_system}
                            />
                            <TextAreaField
                                name="notes"
                                label="Notes"
                                errors={errors}
                                defaultValue={writer?.notes}
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
