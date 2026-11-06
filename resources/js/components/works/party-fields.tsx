import { useMemo } from 'react';
import {
    PercentageField,
    SelectField,
    TerritoryField,
    TextField,
} from '@/components/form-fields';
import type { Errors } from '@/components/form-fields';
import { Combobox } from '@/components/ui/combobox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import type { WorkParty, WorkPartyOptions } from '@/types';

export const YES_NO = [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
];

export const WRITER_ROLES = ['C', 'A', 'CA', 'AR'];

/**
 * The input name of a party field. Dialogs submit plain names (`role`), while
 * the create form repeats the fields as `parties[0][role]`.
 */
export function partyFieldName(prefix: string, field: string): string {
    return prefix === '' ? field : `${prefix}[${field}]`;
}

type PartyIdentityFieldsProps = {
    /** The input name prefix, empty in dialogs. */
    prefix: string;
    role: string;
    /** The picked writer or publisher id, as text. */
    partyId: string;
    onChange: (changes: { role?: string; partyId?: string }) => void;
    options: Pick<WorkPartyOptions, 'roles' | 'writers' | 'publishers'>;
    errors: Errors;
};

/**
 * The role, the writer or publisher it applies to, and the name shown on the
 * work. A writer role picks a writer, and E and SE pick a publisher. The
 * grid classes of the caller place the fields.
 */
export function PartyIdentityFields({
    prefix,
    role,
    partyId,
    onChange,
    options,
    errors,
}: PartyIdentityFieldsProps) {
    const isWriterRole = WRITER_ROLES.includes(role);
    const roleName = partyFieldName(prefix, 'role');
    const partyName = partyFieldName(
        prefix,
        isWriterRole ? 'writer_id' : 'publisher_id',
    );
    const partyError =
        errors[partyFieldName(prefix, 'writer_id')] ??
        errors[partyFieldName(prefix, 'publisher_id')];

    const writerOptions = useMemo(
        () =>
            options.writers.map((writer) => ({
                value: String(writer.id),
                label: `${writer.name} (${writer.code})`,
            })),
        [options.writers],
    );
    const publisherOptions = useMemo(
        () =>
            options.publishers.map((publisher) => ({
                value: String(publisher.id),
                label: `${publisher.name} (${publisher.code})`,
            })),
        [options.publishers],
    );

    return (
        <>
            <Field data-invalid={!!errors[roleName]}>
                <FieldLabel htmlFor={`field-${roleName}`}>Role</FieldLabel>
                <input type="hidden" name={roleName} value={role} />
                <Combobox
                    id={`field-${roleName}`}
                    options={options.roles.map((item) => ({
                        value: item.value,
                        label: `${item.value} · ${item.label}`,
                    }))}
                    value={role}
                    onValueChange={(next) => {
                        const nextRole = String(next);
                        const changesType =
                            WRITER_ROLES.includes(nextRole) !== isWriterRole;

                        onChange({
                            role: nextRole,
                            partyId: changesType ? '' : partyId,
                        });
                    }}
                    placeholder="Select role"
                    searchPlaceholder="Search..."
                />
                <FieldError>{errors[roleName]}</FieldError>
            </Field>

            <Field data-invalid={!!partyError}>
                <FieldLabel htmlFor={`field-${partyName}`}>
                    {isWriterRole ? 'Writer' : 'Publisher'}
                </FieldLabel>
                <input type="hidden" name={partyName} value={partyId} />
                <Combobox
                    id={`field-${partyName}`}
                    options={isWriterRole ? writerOptions : publisherOptions}
                    value={partyId || null}
                    onValueChange={(next) => {
                        const nextId = String(next);
                        const writer = options.writers.find(
                            (item) => String(item.id) === nextId,
                        );

                        onChange({
                            partyId: nextId,
                            role:
                                isWriterRole && writer?.default_role
                                    ? writer.default_role
                                    : role,
                        });
                    }}
                    aria-invalid={!!partyError}
                    placeholder={
                        isWriterRole ? 'Select writer' : 'Select publisher'
                    }
                    searchPlaceholder="Search..."
                />
                <FieldError>{partyError}</FieldError>
            </Field>

            <TextField
                name={partyFieldName(prefix, 'contributor_name')}
                label="Name on the work"
                className="sm:col-span-2"
                errors={errors}
                placeholder="Leave empty to use the writer or publisher name"
            />
        </>
    );
}

type PartyRightsFieldsProps = {
    prefix: string;
    errors: Errors;
    countries: WorkPartyOptions['countries'];
    /** The row whose values fill the fields. Leave empty for a new party. */
    party?: WorkParty;
};

/**
 * The four shares, whether TAJA controls them and the territories. Shared by
 * the create form and the add, change and correct dialogs.
 */
export function PartyRightsFields({
    prefix,
    errors,
    countries,
    party,
}: PartyRightsFieldsProps) {
    return (
        <>
            <PercentageField
                name={partyFieldName(prefix, 'performance_share')}
                label="Performance share"
                errors={errors}
                defaultValue={party?.performance_share}
            />
            <PercentageField
                name={partyFieldName(prefix, 'mechanical_share')}
                label="Mechanical share"
                errors={errors}
                defaultValue={party?.mechanical_share}
            />
            <PercentageField
                name={partyFieldName(prefix, 'sync_share')}
                label="Sync share"
                errors={errors}
                defaultValue={party?.sync_share}
            />
            <PercentageField
                name={partyFieldName(prefix, 'lyrics_share')}
                label="Lyrics share"
                errors={errors}
                defaultValue={party?.lyrics_share}
            />
            <SelectField
                name={partyFieldName(prefix, 'is_controlled')}
                label="Controlled by TAJA"
                className="sm:col-span-2"
                errors={errors}
                options={YES_NO}
                defaultValue={party?.is_controlled === false ? '0' : '1'}
            />
            <TerritoryField
                name={partyFieldName(prefix, 'territories_controlled')}
                label="Territories controlled"
                errors={errors}
                countries={countries}
                defaultValues={party?.territories_controlled ?? ['WW']}
                className="sm:col-span-2"
            />
            <TerritoryField
                name={partyFieldName(prefix, 'territory_exclusions')}
                label="Territory exclusions"
                errors={errors}
                countries={countries}
                defaultValues={party?.territory_exclusions ?? []}
                className="sm:col-span-2"
            />
        </>
    );
}

/**
 * The roles a row can change to: a writer row stays a writer and a publisher
 * row stays a publisher, as the server requires.
 */
export function sameTypeRoleOptions(
    roles: WorkPartyOptions['roles'],
    party: WorkParty,
): { value: string; label: string }[] {
    return roles
        .filter(
            (role) =>
                WRITER_ROLES.includes(role.value) ===
                (party.party_type === 'writer'),
        )
        .map((role) => ({
            value: role.value,
            label: `${role.value} · ${role.label}`,
        }));
}
