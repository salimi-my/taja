import { Eraser } from 'lucide-react';
import type { ReactNode } from 'react';
import WorkPartyController from '@/actions/App/Http/Controllers/WorkPartyController';
import { DateField, SelectField } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import { PartyActionDialog } from '@/components/works/party-action-dialog';
import {
    PartyRightsFields,
    sameTypeRoleOptions,
} from '@/components/works/party-fields';
import type { WorkParty, WorkPartyOptions } from '@/types';

type PartyCorrectDialogProps = {
    workId: number;
    party: WorkParty;
    options: WorkPartyOptions;
    /** The button that opens the dialog. */
    children?: ReactNode;
};

/**
 * Corrects a row that was entered wrongly. The old row is hidden from the
 * current view but stays in the history, and the replacement keeps the old
 * dates unless they are changed here.
 */
export function PartyCorrectDialog({
    workId,
    party,
    options,
    children,
}: PartyCorrectDialogProps) {
    return (
        <PartyActionDialog
            title={`Correct entry of ${party.contributor_name}`}
            description="Use this when the row was entered wrongly. The old row is kept in the history but no longer counts. To change rights from a date, use Change instead."
            trigger={
                children ?? (
                    <Button variant="ghost" size="sm">
                        <Eraser />
                        Correct
                    </Button>
                )
            }
            form={WorkPartyController.correct.form({
                work: workId,
                party: party.id,
            })}
            submitLabel="Save correction"
            processingLabel="Saving..."
        >
            {(errors) => (
                <>
                    <DateField
                        name="effective_start_date"
                        label="Effective from"
                        errors={errors}
                        defaultValue={party.effective_start_date}
                    />
                    <DateField
                        name="effective_end_date"
                        label="Effective until"
                        errors={errors}
                        defaultValue={party.effective_end_date}
                        description="Leave empty while the row is still in effect."
                    />
                    <SelectField
                        name="role"
                        label="Role"
                        className="sm:col-span-2"
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
