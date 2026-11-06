<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\Work;
use App\Models\WorkParty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Takes a party off a work from a given date. The row is not deleted: its last
 * day in effect becomes the day before the date, and a `PartyRemoved` entry is
 * logged.
 */
class RemoveParty
{
    public function __construct(private RecordRightsHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The required `effective_date` (the first day the party is no longer on the work) and an optional `remarks`.
     *
     * @throws ValidationException
     */
    public function __invoke(WorkParty $party, array $data, ?int $userId = null): WorkParty
    {
        return DB::transaction(function () use ($party, $data, $userId): WorkParty {
            $work = Work::query()->lockForUpdate()->findOrFail($party->work_id);
            $row = $work->parties()->findOrFail($party->id);
            $date = CarbonImmutable::parse($data['effective_date'])->startOfDay();

            if ($row->superseded_at !== null || $row->effective_end_date !== null) {
                throw ValidationException::withMessages(['party' => 'This row has already ended.']);
            }

            if (! $date->greaterThan($row->effective_start_date)) {
                throw ValidationException::withMessages(['effective_date' => 'The date must be after the row started on '.$row->effective_start_date->toDateString().'.']);
            }

            $previous = RecordRightsHistory::partySnapshot($row);

            $row->update(['effective_end_date' => $date->subDay()]);

            ($this->recordHistory)(
                $work,
                RightsChangeType::PartyRemoved,
                $date,
                party: $row,
                previous: $previous,
                userId: $userId,
                remarks: $data['remarks'] ?? null,
            );

            return $row;
        });
    }
}
