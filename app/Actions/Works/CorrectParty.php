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
 * Corrects a row that was entered wrongly (business rule 10, "Correct entry").
 * The old row is superseded, which hides it from the current and point-in-time
 * views while it stays in the history, and a replacement keeps the old
 * effective dates unless they are corrected too.
 */
class CorrectParty
{
    private const DATE_FIELDS = ['effective_start_date', 'effective_end_date'];

    public function __construct(
        private RecordRightsHistory $recordHistory,
        private ValidateWorkParties $validateParties,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The corrected rights fields (see `WorkParty::RIGHTS_FIELDS`), optional `effective_start_date` and `effective_end_date`, and optional `remarks`.
     *
     * @throws ValidationException
     */
    public function __invoke(WorkParty $party, array $data, ?int $userId = null): WorkParty
    {
        return DB::transaction(function () use ($party, $data, $userId): WorkParty {
            $work = Work::query()->lockForUpdate()->findOrFail($party->work_id);
            $old = $work->parties()->findOrFail($party->id);

            if ($old->superseded_at !== null) {
                throw ValidationException::withMessages(['party' => 'This row was already corrected. Correct the current row instead.']);
            }

            $attributes = [
                'writer_id' => $old->writer_id,
                'publisher_id' => $old->publisher_id,
                'contributor_name' => $old->contributor_name,
                ...$old->only([...WorkParty::RIGHTS_FIELDS, ...self::DATE_FIELDS]),
                ...Arr::only($data, [...WorkParty::RIGHTS_FIELDS, ...self::DATE_FIELDS]),
            ];

            $candidate = $work->parties()->make($attributes);

            if (isset($candidate->effective_end_date)
                && CarbonImmutable::parse($candidate->effective_end_date)->lessThan($candidate->effective_start_date)) {
                throw ValidationException::withMessages(['effective_end_date' => 'The end date cannot be before the start date.']);
            }

            $previous = RecordRightsHistory::partySnapshot($old);
            $new = RecordRightsHistory::partySnapshot($candidate);
            $changed = array_keys(RecordRightsHistory::changedAttributes($previous, $new)['new']);

            if ($changed === []) {
                throw ValidationException::withMessages(['party' => 'Nothing was changed.']);
            }

            $old->update(['superseded_at' => now()]);
            $candidate->save();

            ($this->recordHistory)(
                $work,
                array_diff($changed, self::DATE_FIELDS) === []
                    ? RightsChangeType::DatesChanged
                    : RightsChangeType::Correction,
                $candidate->effective_start_date,
                party: $candidate,
                previous: $previous,
                new: $new,
                userId: $userId,
                remarks: $data['remarks'] ?? null,
            );

            ($this->validateParties)($work);

            return $candidate;
        });
    }
}
