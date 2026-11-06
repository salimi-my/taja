import { UserMinus } from 'lucide-react';
import type { ReactNode } from 'react';
import WorkPartyController from '@/actions/App/Http/Controllers/WorkPartyController';
import { DateField } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import { PartyActionDialog } from '@/components/works/party-action-dialog';
import { todayIso } from '@/lib/works';
import type { WorkParty } from '@/types';

type PartyRemoveDialogProps = {
    workId: number;
    party: WorkParty;
    /** The button that opens the dialog. */
    children?: ReactNode;
};

/**
 * Takes a party off the work from a date. The row is kept: its last day in
 * effect becomes the day before the date.
 */
export function PartyRemoveDialog({
    workId,
    party,
    children,
}: PartyRemoveDialogProps) {
    return (
        <PartyActionDialog
            title={`Remove ${party.contributor_name}`}
            description="The party stays on record until the day before the date, and the removal is added to the history."
            trigger={
                children ?? (
                    <Button variant="ghost" size="sm">
                        <UserMinus />
                        Remove
                    </Button>
                )
            }
            form={WorkPartyController.destroy.form({
                work: workId,
                party: party.id,
            })}
            submitLabel="Remove party"
            processingLabel="Removing..."
            destructive
        >
            {(errors) => (
                <DateField
                    name="effective_date"
                    label="Removed from"
                    className="sm:col-span-2"
                    errors={errors}
                    defaultValue={todayIso()}
                    description={`Must be after ${party.effective_start_date}.`}
                />
            )}
        </PartyActionDialog>
    );
}
