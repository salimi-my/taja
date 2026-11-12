<?php

namespace App\Actions\Masters;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\MasterChangeType;
use App\Models\Publisher;
use App\Models\WorkParty;
use Illuminate\Support\Facades\DB;

/**
 * Updates a publisher, then logs an `Updated` entry holding what changed.
 * Nothing is logged when nothing changed. A rename also renames the party rows
 * that still carry the old name, so works show the new name.
 */
class UpdatePublisher
{
    public function __construct(private RecordMasterHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The publisher fields.
     */
    public function __invoke(Publisher $publisher, array $data, ?int $userId = null): Publisher
    {
        return DB::transaction(function () use ($publisher, $data, $userId): Publisher {
            $previous = RecordMasterHistory::snapshot($publisher);

            $previousName = $publisher->name;

            $publisher->update($data);

            if ($publisher->name !== $previousName) {
                WorkParty::query()
                    ->where('publisher_id', $publisher->id)
                    ->where('contributor_name', $previousName)
                    ->update(['contributor_name' => $publisher->name]);
            }

            $changes = RecordRightsHistory::changedAttributes($previous, RecordMasterHistory::snapshot($publisher->refresh()));

            if ($changes['new'] !== []) {
                ($this->recordHistory)(
                    $publisher,
                    MasterChangeType::Updated,
                    previous: $changes['previous'],
                    new: $changes['new'],
                    userId: $userId,
                );
            }

            return $publisher;
        });
    }
}
