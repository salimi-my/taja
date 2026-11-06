<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\Work;
use App\Models\WorkParty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Changes a party's role, shares or territories from a given date (business
 * rule 10, "Change from a date"). The open row ends the day before and a new
 * row starts on the date, so nothing is overwritten.
 */
class ChangePartyFromDate
{
    public function __construct(
        private RecordRightsHistory $recordHistory,
        private ValidateWorkParties $validateParties,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The new rights fields (see `WorkParty::RIGHTS_FIELDS`), the required `effective_date` and an optional `remarks`.
     *
     * @throws ValidationException
     */
    public function __invoke(WorkParty $party, array $data, ?int $userId = null): WorkParty
    {
        return DB::transaction(function () use ($party, $data, $userId): WorkParty {
            $work = Work::query()->lockForUpdate()->findOrFail($party->work_id);
            $old = $work->parties()->findOrFail($party->id);
            $date = CarbonImmutable::parse($data['effective_date'])->startOfDay();

            if ($old->superseded_at !== null || $old->effective_end_date !== null) {
                throw ValidationException::withMessages(['party' => 'This row has already ended. Change the row that is in effect instead.']);
            }

            if (! $date->greaterThan($old->effective_start_date)) {
                throw ValidationException::withMessages(['effective_date' => 'The date must be after the row started on '.$old->effective_start_date->toDateString().'.']);
            }

            $candidate = $work->parties()->make([
                'writer_id' => $old->writer_id,
                'publisher_id' => $old->publisher_id,
                'contributor_name' => $old->contributor_name,
                ...$old->only(WorkParty::RIGHTS_FIELDS),
                ...Arr::only($data, WorkParty::RIGHTS_FIELDS),
                'effective_start_date' => $date,
                'effective_end_date' => null,
            ]);

            $previous = RecordRightsHistory::partySnapshot($old);
            $changed = array_keys(RecordRightsHistory::changedAttributes(
                Arr::only($previous, WorkParty::RIGHTS_FIELDS),
                Arr::only(RecordRightsHistory::partySnapshot($candidate), WorkParty::RIGHTS_FIELDS),
            )['new']);

            if ($changed === []) {
                throw ValidationException::withMessages(['party' => 'Nothing was changed.']);
            }

            $old->update(['effective_end_date' => $date->subDay()]);
            $candidate->save();

            ($this->recordHistory)(
                $work,
                array_diff($changed, ['territories_controlled', 'territory_exclusions']) === []
                    ? RightsChangeType::TerritoryChanged
                    : RightsChangeType::ShareUpdated,
                $date,
                party: $candidate,
                previous: $previous,
                new: RecordRightsHistory::partySnapshot($candidate),
                userId: $userId,
                remarks: $data['remarks'] ?? null,
            );

            ($this->validateParties)($work);

            return $candidate;
        });
    }
}
