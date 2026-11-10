<?php

namespace App\Actions\Masters;

use App\Enums\MasterChangeType;
use App\Models\Publisher;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes a publisher and logs a `Deleted` entry holding its last state.
 */
class DeletePublisher
{
    public function __construct(private RecordMasterHistory $recordHistory) {}

    public function __invoke(Publisher $publisher, ?int $userId = null): void
    {
        DB::transaction(function () use ($publisher, $userId): void {
            $previous = RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($publisher));

            $publisher->delete();

            ($this->recordHistory)($publisher, MasterChangeType::Deleted, previous: $previous, userId: $userId);
        });
    }
}
