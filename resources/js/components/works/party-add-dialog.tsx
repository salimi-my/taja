import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import WorkPartyController from '@/actions/App/Http/Controllers/WorkPartyController';
import { DateField } from '@/components/form-fields';
import type { Errors } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import { PartyActionDialog } from '@/components/works/party-action-dialog';
import {
    PartyIdentityFields,
    PartyRightsFields,
} from '@/components/works/party-fields';
import { todayIso } from '@/lib/works';
import type { WorkPartyOptions } from '@/types';

type PartyAddDialogProps = {
    workId: number;
    options: WorkPartyOptions;
};

type AddPartyFieldsProps = {
    options: WorkPartyOptions;
    errors: Errors;
};

/** Holds the role and the picked party, so the state resets with the dialog. */
function AddPartyFields({ options, errors }: AddPartyFieldsProps): ReactNode {
    const [role, setRole] = useState('C');
    const [partyId, setPartyId] = useState('');

    return (
        <>
            <DateField
                name="effective_start_date"
                label="Effective from"
                className="sm:col-span-2"
                errors={errors}
                defaultValue={todayIso()}
                description="The first day the party owns these shares."
            />
            <PartyIdentityFields
                prefix=""
                role={role}
                partyId={partyId}
                onChange={(changes) => {
                    if (changes.role !== undefined) {
                        setRole(changes.role);
                    }

                    if (changes.partyId !== undefined) {
                        setPartyId(changes.partyId);
                    }
                }}
                options={options}
                errors={errors}
            />
            <PartyRightsFields
                prefix=""
                errors={errors}
                countries={options.countries}
            />
        </>
    );
}

/** Adds a writer or publisher to an existing work from a date. */
export function PartyAddDialog({ workId, options }: PartyAddDialogProps) {
    return (
        <PartyActionDialog
            title="Add party"
            description="Add a writer or publisher to this work. The shares of each rights type must not add up to more than 100 on any day."
            trigger={
                <Button size="sm">
                    <Plus />
                    Add party
                </Button>
            }
            form={WorkPartyController.store.form(workId)}
            submitLabel="Add party"
            processingLabel="Adding..."
        >
            {(errors) => <AddPartyFields options={options} errors={errors} />}
        </PartyActionDialog>
    );
}
