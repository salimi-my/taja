import { CalendarClock, Ellipsis, Eraser, UserMinus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PartyChangeDialog } from '@/components/works/party-change-dialog';
import { PartyCorrectDialog } from '@/components/works/party-correct-dialog';
import { PartyRemoveDialog } from '@/components/works/party-remove-dialog';
import type { WorkParty, WorkPartyOptions } from '@/types';

type PartyRowActionsProps = {
    workId: number;
    party: WorkParty;
    options: WorkPartyOptions;
};

/**
 * The actions of a party row: Change from a date as a button, since it is the
 * common one, and Correct and Remove in a menu. A row that already ended can
 * only be corrected.
 */
export function PartyRowActions({
    workId,
    party,
    options,
}: PartyRowActionsProps) {
    const [isCorrecting, setIsCorrecting] = useState(false);
    const [isRemoving, setIsRemoving] = useState(false);
    const isInEffect = party.effective_end_date === null;

    return (
        <div className="flex items-center gap-1">
            {isInEffect && (
                <PartyChangeDialog
                    workId={workId}
                    party={party}
                    options={options}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        title="Change from a date"
                    >
                        <CalendarClock />
                        Change
                    </Button>
                </PartyChangeDialog>
            )}

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        aria-label={`More actions for ${party.contributor_name}`}
                        variant="ghost"
                        className="flex size-8 p-0 data-[state=open]:bg-muted"
                    >
                        <Ellipsis className="size-4" aria-hidden="true" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-44">
                    <DropdownMenuItem onSelect={() => setIsCorrecting(true)}>
                        <Eraser aria-hidden="true" />
                        Correct entry
                    </DropdownMenuItem>
                    {isInEffect && (
                        <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive-hover"
                                onSelect={() => setIsRemoving(true)}
                            >
                                <UserMinus aria-hidden="true" />
                                Remove
                            </DropdownMenuItem>
                        </>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            <PartyCorrectDialog
                workId={workId}
                party={party}
                options={options}
                open={isCorrecting}
                onOpenChange={setIsCorrecting}
            />
            {isInEffect && (
                <PartyRemoveDialog
                    workId={workId}
                    party={party}
                    open={isRemoving}
                    onOpenChange={setIsRemoving}
                />
            )}
        </div>
    );
}
