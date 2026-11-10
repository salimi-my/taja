<?php

namespace App\Actions\Masters;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\MasterChangeType;
use App\Models\Publisher;
use Illuminate\Support\Facades\DB;

/**
 * Updates a publisher, then logs an `Updated` entry holding what changed.
 * Nothing is logged when nothing changed.
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

            $publisher->update($data);

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
