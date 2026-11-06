<?php

namespace App\Actions\Works;

use App\Enums\RightsChangeType;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Adds a writer or publisher to a work and logs a `PartyAdded` entry. Used when
 * a work is created (form and import) and to add a party to an existing work.
 */
class AddParty
{
    public function __construct(
        private ResolveInitialEffectiveDate $resolveEffectiveDate,
        private RecordRightsHistory $recordHistory,
        private ValidateWorkParties $validateParties,
    ) {}

    /**
     * @param  array<string, mixed>  $party  The party fields. `effective_start_date` and `contributor_name` are optional.
     * @param  bool  $validateShares  Check the work's share sums afterwards. Callers that already validated pass `false`.
     */
    public function __invoke(
        Work $work,
        array $party,
        ?int $userId = null,
        ?int $csvImportId = null,
        ?string $remarks = null,
        bool $validateShares = true,
    ): WorkParty {
        return DB::transaction(function () use ($work, $party, $userId, $csvImportId, $remarks, $validateShares): WorkParty {
            $master = isset($party['writer_id'])
                ? Writer::query()->findOrFail((int) $party['writer_id'])
                : Publisher::query()->findOrFail((int) $party['publisher_id']);

            $row = $work->parties()->create([
                ...Arr::except($party, ['contributor_name', 'effective_start_date', 'effective_date', 'remarks']),
                'contributor_name' => $party['contributor_name'] ?? $master->name,
                'effective_start_date' => $party['effective_start_date']
                    ?? ($this->resolveEffectiveDate)($work->administration_start_date, $master),
            ]);

            ($this->recordHistory)(
                $work,
                RightsChangeType::PartyAdded,
                $row->effective_start_date,
                party: $row,
                new: RecordRightsHistory::partySnapshot($row),
                userId: $userId,
                csvImportId: $csvImportId,
                remarks: $remarks,
            );

            if ($validateShares) {
                ($this->validateParties)($work);
            }

            return $row;
        });
    }
}
