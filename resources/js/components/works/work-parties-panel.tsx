import { router } from '@inertiajs/react';
import { CalendarClock, Eraser, Info, UserMinus, X } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { PartyAddDialog } from '@/components/works/party-add-dialog';
import { PartyChangeDialog } from '@/components/works/party-change-dialog';
import { PartyCorrectDialog } from '@/components/works/party-correct-dialog';
import { PartyRemoveDialog } from '@/components/works/party-remove-dialog';
import { WorkPartiesTable } from '@/components/works/work-parties-table';
import { show } from '@/routes/works';
import type { WorkParty, WorkPartyOptions } from '@/types';

type WorkPartiesPanelProps = {
    workId: number;
    parties: WorkParty[];
    /** The date the parties are shown for, or `null` for today. */
    asOf: string | null;
    /** The form options. Only sent to users who may change the work. */
    partyOptions: WorkPartyOptions | null;
};

/**
 * The Parties tab: the rows in effect today or on a picked date, with the
 * add, change, correct and remove actions for admins when viewing today.
 */
export function WorkPartiesPanel({
    workId,
    parties,
    asOf,
    partyOptions,
}: WorkPartiesPanelProps) {
    const canManage = partyOptions !== null && asOf === null;

    const showAsOf = (date: string) =>
        router.get(show.url(workId), date === '' ? {} : { as_of: date }, {
            only: ['parties', 'asOf'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div className="flex flex-wrap items-end gap-2">
                    <div className="w-52 space-y-1">
                        <label
                            htmlFor="parties-as-of"
                            className="text-xs text-muted-foreground"
                        >
                            Show parties as of
                        </label>
                        <DatePicker
                            id="parties-as-of"
                            value={asOf ?? ''}
                            onChange={showAsOf}
                            clearable={false}
                            placeholder="Today"
                        />
                    </div>
                    {asOf !== null && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => showAsOf('')}
                        >
                            <X />
                            Back to today
                        </Button>
                    )}
                </div>

                {canManage && (
                    <PartyAddDialog workId={workId} options={partyOptions} />
                )}
            </div>

            {asOf !== null && (
                <Alert>
                    <Info />
                    <AlertDescription>
                        Showing the parties in effect on {formatDate(asOf)}. Go
                        back to today to add, change or remove parties.
                    </AlertDescription>
                </Alert>
            )}

            <WorkPartiesTable
                parties={parties}
                emptyDescription={
                    asOf === null
                        ? 'Parties appear here once they are added to the work.'
                        : 'No party was in effect on this date.'
                }
                renderActions={
                    canManage
                        ? (party) => (
                              <>
                                  {party.effective_end_date === null && (
                                      <PartyChangeDialog
                                          workId={workId}
                                          party={party}
                                          options={partyOptions}
                                      >
                                          <Button
                                              variant="ghost"
                                              size="sm"
                                              title="Change from a date"
                                          >
                                              <CalendarClock />
                                              Change
                                          </Button>
                                      </PartyChangeDialog>
                                  )}
                                  <PartyCorrectDialog
                                      workId={workId}
                                      party={party}
                                      options={partyOptions}
                                  >
                                      <Button
                                          variant="ghost"
                                          size="sm"
                                          title="Correct an entry made in error"
                                      >
                                          <Eraser />
                                          Correct
                                      </Button>
                                  </PartyCorrectDialog>
                                  {party.effective_end_date === null && (
                                      <PartyRemoveDialog
                                          workId={workId}
                                          party={party}
                                      >
                                          <Button
                                              variant="ghost"
                                              size="sm"
                                              title="Remove from a date"
                                          >
                                              <UserMinus />
                                              Remove
                                          </Button>
                                      </PartyRemoveDialog>
                                  )}
                              </>
                          )
                        : undefined
                }
            />
        </div>
    );
}
