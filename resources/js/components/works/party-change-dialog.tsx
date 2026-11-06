import { CalendarClock } from 'lucide-react';
import type { ReactNode } from 'react';
import WorkPartyController from '@/actions/App/Http/Controllers/WorkPartyController';
import { DateField, SelectField } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import { PartyActionDialog } from '@/components/works/party-action-dialog';
import {
    PartyRightsFields,
    sameTypeRoleOptions,
} from '@/components/works/party-fields';
import { todayIso } from '@/lib/works';
import type { WorkParty, WorkPartyOptions } from '@/types';

type PartyChangeDialogProps = {
    workId: number;
    party: WorkParty;
    options: WorkPartyOptions;
    /** The button that opens the dialog. */
    children?: ReactNode;
};

/**
 * Changes a party's role, shares or territories from a date. The row in effect
 * ends the day before and a new row starts, so the old values stay on record.
 */
export function PartyChangeDialog({
    workId,
    party,
    options,
    children,
}: PartyChangeDialogProps) {
    return (
        <PartyActionDialog
            title={`Change ${party.contributor_name} from a date`}
            description="The current row ends the day before the date and a new row starts on it. Nothing is overwritten."
            trigger={
                children ?? (
                    <Button variant="ghost" size="sm">
                        <CalendarClock />
                        Change
                    </Button>
                )
            }
            form={WorkPartyController.change.form({
                work: workId,
                party: party.id,
            })}
            submitLabel="Save change"
            processingLabel="Saving..."
        >
            {(errors) => (
                <>
                    <DateField
                        name="effective_date"
                        label="Effective from"
                        errors={errors}
                        defaultValue={todayIso()}
                        description={`Must be after ${party.effective_start_date}.`}
                    />
                    <SelectField
                        name="role"
                        label="Role"
                        errors={errors}
                        options={sameTypeRoleOptions(options.roles, party)}
                        defaultValue={party.role}
                        display="combobox"
                    />
                    <PartyRightsFields
                        prefix=""
                        errors={errors}
                        countries={options.countries}
                        party={party}
                    />
                </>
            )}
        </PartyActionDialog>
    );
}
