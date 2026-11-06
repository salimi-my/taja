import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import WorkController from '@/actions/App/Http/Controllers/WorkController';
import {
    DateField,
    SelectField,
    TextAreaField,
    TextField,
    toBracketErrors,
} from '@/components/form-fields';
import type { Errors } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    PartyIdentityFields,
    PartyRightsFields,
    YES_NO,
} from '@/components/works/party-fields';
import { WorkPartiesTable } from '@/components/works/work-parties-table';
import { MAX_ISRCS, MAX_PARTIES, todayIso } from '@/lib/works';
import type {
    WorkCreateOptions,
    WorkDetail,
    WorkEditOptions,
    WorkParty,
} from '@/types';

type WorkFormProps = {
    /** The work being edited. Leave empty to create a new work. */
    work?: WorkDetail;
    /** The existing parties of the work, shown read-only when editing. */
    parties?: WorkParty[];
    /** Registration and conflict options, plus the party pickers when creating. */
    options: WorkEditOptions & Partial<WorkCreateOptions>;
    /** Where the cancel button goes back to. */
    cancelHref: InertiaLinkProps['href'];
};

type IsrcRow = {
    /** A stable key so typed values stay with their row when another is removed. */
    key: number;
    value: string;
};

type IsrcRepeaterProps = {
    initialIsrcs: string[];
    errors: Errors;
};

/** A list of ISRC inputs, submitted as `isrcs[]`. */
function IsrcRepeater({ initialIsrcs, errors }: IsrcRepeaterProps) {
    const [nextKey, setNextKey] = useState(initialIsrcs.length + 1);
    const [rows, setRows] = useState<IsrcRow[]>(
        initialIsrcs.length > 0
            ? initialIsrcs.map((value, index) => ({ key: index, value }))
            : [{ key: 0, value: '' }],
    );

    const addRow = () => {
        setRows((current) => [...current, { key: nextKey, value: '' }]);
        setNextKey((current) => current + 1);
    };

    return (
        <Field className="sm:col-span-2">
            <FieldLabel>ISRCs</FieldLabel>
            <div className="flex flex-col gap-2">
                {rows.map((row, index) => (
                    <div key={row.key} className="space-y-1">
                        <div className="flex items-center gap-2">
                            <Input
                                name="isrcs[]"
                                defaultValue={row.value}
                                placeholder="MYABC2600001"
                                autoComplete="off"
                                aria-label={`ISRC ${index + 1}`}
                                aria-invalid={!!errors[`isrcs.${index}`]}
                                className="font-mono"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Remove ISRC"
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
                        <FieldError>{errors[`isrcs.${index}`]}</FieldError>
                    </div>
                ))}
            </div>
            <FieldDescription>
                12 characters, e.g. MYABC2600001. Hyphens are removed.
            </FieldDescription>
            <FieldError>{errors.isrcs}</FieldError>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={rows.length >= MAX_ISRCS}
                    onClick={addRow}
                >
                    <Plus />
                    Add ISRC
                </Button>
            </div>
        </Field>
    );
}

type PartyRow = {
    key: number;
    role: string;
    /** The picked writer or publisher id, as text. */
    partyId: string;
};

type PartiesRepeaterProps = {
    options: Pick<
        WorkCreateOptions,
        'roles' | 'countries' | 'writers' | 'publishers'
    >;
    errors: Errors;
};

/**
 * The parties a new work is created with. Each row picks a role, then a writer
 * (writer roles) or a publisher (E and SE), plus shares and territories.
 */
function PartiesRepeater({ options, errors }: PartiesRepeaterProps) {
    const [nextKey, setNextKey] = useState(1);
    const [rows, setRows] = useState<PartyRow[]>([
        { key: 0, role: 'C', partyId: '' },
    ]);
    const bracketErrors = useMemo(() => toBracketErrors(errors), [errors]);

    const updateRow = (key: number, changes: Partial<PartyRow>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    const addRow = () => {
        setRows((current) => [
            ...current,
            { key: nextKey, role: 'C', partyId: '' },
        ]);
        setNextKey((current) => current + 1);
    };

    return (
        <FieldSet>
            <FieldLegend>Parties</FieldLegend>
            <FieldDescription>
                The writers and publishers that own this work. The shares of
                each rights type must not add up to more than 100. The effective
                date is the administration start date, otherwise the party's
                agreement start date, otherwise today.
            </FieldDescription>
            <FieldGroup className="flex flex-col gap-4">
                {rows.map((row, index) => {
                    const prefix = `parties[${index}]`;
                    return (
                        <div
                            key={row.key}
                            className="flex items-start gap-2 rounded-lg border p-3"
                        >
                            <div className="grid min-w-0 flex-1 gap-3 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                                <PartyIdentityFields
                                    prefix={prefix}
                                    role={row.role}
                                    partyId={row.partyId}
                                    onChange={(changes) =>
                                        updateRow(row.key, changes)
                                    }
                                    options={options}
                                    errors={bracketErrors}
                                />
                                <PartyRightsFields
                                    prefix={prefix}
                                    errors={bracketErrors}
                                    countries={options.countries}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Remove party"
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
                    );
                })}
                <FieldError>{errors.parties}</FieldError>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={rows.length >= MAX_PARTIES}
                        onClick={addRow}
                    >
                        <Plus />
                        Add party
                    </Button>
                </div>
            </FieldGroup>
        </FieldSet>
    );
}

export function WorkForm({
    work,
    parties = [],
    options,
    cancelHref,
}: WorkFormProps) {
    const isEditing = work !== undefined;

    return (
        <Form
            {...(isEditing
                ? WorkController.update.form(work.id)
                : WorkController.store.form())}
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
                                name="macp_work_id"
                                label="MACP Work ID"
                                errors={errors}
                                defaultValue={work?.macp_work_id}
                                description={
                                    isEditing
                                        ? `TAP number: ${work.tap_number}`
                                        : 'The TAP number is generated automatically.'
                                }
                            />
                            <TextField
                                name="iswc"
                                label="ISWC"
                                errors={errors}
                                defaultValue={work?.iswc}
                                placeholder="T1234567890"
                            />
                            <TextField
                                name="english_title"
                                label="English title"
                                errors={errors}
                                defaultValue={work?.english_title}
                                description="At least one of the English and other title is required."
                            />
                            <TextField
                                name="other_title"
                                label="Other title"
                                errors={errors}
                                defaultValue={work?.other_title}
                            />
                            <TextField
                                name="original_work_title"
                                label="Original work title"
                                errors={errors}
                                defaultValue={work?.original_work_title}
                            />
                            <TextField
                                name="sub_title_work"
                                label="Sub title"
                                errors={errors}
                                defaultValue={work?.sub_title_work}
                            />
                            <TextField
                                name="society_no"
                                label="Society number"
                                className="sm:col-span-2"
                                errors={errors}
                                defaultValue={work?.society_no}
                            />
                            <IsrcRepeater
                                initialIsrcs={work?.isrcs ?? []}
                                errors={errors}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Details</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="work_type"
                                label="Work type"
                                errors={errors}
                                defaultValue={work?.work_type}
                                placeholder="e.g. ORG"
                            />
                            <TextField
                                name="genre"
                                label="Genre"
                                errors={errors}
                                defaultValue={work?.genre}
                            />
                            <TextField
                                name="language"
                                label="Language"
                                errors={errors}
                                defaultValue={work?.language}
                            />
                            <TextField
                                name="duration"
                                label="Duration"
                                errors={errors}
                                defaultValue={work?.duration}
                                placeholder="HH:MM:SS"
                            />
                            <TextField
                                name="artist_name"
                                label="Artist"
                                errors={errors}
                                defaultValue={work?.artist_name}
                            />
                            <DateField
                                name="release_date"
                                label="Release date"
                                errors={errors}
                                defaultValue={work?.release_date}
                            />
                            <SelectField
                                name="is_public_domain"
                                label="Public domain"
                                errors={errors}
                                options={YES_NO}
                                defaultValue={
                                    work?.is_public_domain ? '1' : '0'
                                }
                            />
                            <TextField
                                name="song_link"
                                label="Song link"
                                errors={errors}
                                defaultValue={work?.song_link}
                                placeholder="https://"
                            />
                            <TextAreaField
                                name="remark"
                                label="Remark"
                                errors={errors}
                                defaultValue={work?.remark}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Registration</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <SelectField
                                name="registration_status"
                                label="Registration status"
                                errors={errors}
                                options={options.registrationStatuses}
                                defaultValue={
                                    work?.registration_status ??
                                    options.registrationStatuses[0]?.value ??
                                    ''
                                }
                            />
                            <DateField
                                name="registration_date"
                                label="Registration date"
                                errors={errors}
                                defaultValue={work?.registration_date}
                            />
                            <DateField
                                name="administration_start_date"
                                label="Administration start date"
                                errors={errors}
                                defaultValue={work?.administration_start_date}
                                description="The default effective date of the parties."
                            />
                            <DateField
                                name="administration_end_date"
                                label="Administration end date"
                                errors={errors}
                                defaultValue={work?.administration_end_date}
                            />
                            <SelectField
                                name="conflict_status"
                                label="Conflict status"
                                className="sm:col-span-2"
                                errors={errors}
                                options={options.conflictStatuses}
                                defaultValue={
                                    work?.conflict_status ??
                                    options.conflictStatuses[0]?.value ??
                                    ''
                                }
                            />
                            <TextAreaField
                                name="conflict_notes"
                                label="Conflict notes"
                                errors={errors}
                                defaultValue={work?.conflict_notes}
                            />
                        </FieldGroup>
                    </FieldSet>

                    <FieldSet>
                        <FieldLegend>Society references</FieldLegend>
                        <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                            <TextField
                                name="compass_work_internal_no"
                                label="COMPASS internal no."
                                errors={errors}
                                defaultValue={work?.compass_work_internal_no}
                            />
                            <TextField
                                name="compass_work_type"
                                label="COMPASS work type"
                                errors={errors}
                                defaultValue={work?.compass_work_type}
                            />
                            <DateField
                                name="compass_start_date"
                                label="COMPASS start date"
                                className="sm:col-span-2"
                                errors={errors}
                                defaultValue={work?.compass_start_date}
                            />
                            <TextField
                                name="wami_work_internal_no"
                                label="WAMI internal no."
                                errors={errors}
                                defaultValue={work?.wami_work_internal_no}
                            />
                            <TextField
                                name="wami_work_type"
                                label="WAMI work type"
                                errors={errors}
                                defaultValue={work?.wami_work_type}
                            />
                        </FieldGroup>
                    </FieldSet>

                    {isEditing && (
                        <FieldSet>
                            <FieldLegend>Change record</FieldLegend>
                            <FieldDescription>
                                Every change to a work is saved in its history
                                with the date it takes effect.
                            </FieldDescription>
                            <FieldGroup className="grid gap-4 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                                <DateField
                                    name="effective_date"
                                    label="Effective date"
                                    errors={errors}
                                    defaultValue={todayIso()}
                                />
                                <TextAreaField
                                    name="remarks"
                                    label="Remarks"
                                    errors={errors}
                                />
                            </FieldGroup>
                        </FieldSet>
                    )}

                    {isEditing ? (
                        <FieldSet>
                            <FieldLegend>Parties</FieldLegend>
                            <FieldDescription>
                                Rights data is never overwritten, so parties
                                cannot be edited here. Changes to shares,
                                territories and dates will be made from the
                                Parties tab of the work.
                            </FieldDescription>
                            <WorkPartiesTable parties={parties} />
                        </FieldSet>
                    ) : (
                        <PartiesRepeater
                            options={{
                                roles: options.roles ?? [],
                                countries: options.countries ?? [],
                                writers: options.writers ?? [],
                                publishers: options.publishers ?? [],
                            }}
                            errors={errors}
                        />
                    )}

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
