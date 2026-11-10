<?php

namespace App\Actions\Masters;

use App\Enums\MasterChangeType;
use App\Models\Writer;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes a writer and logs a `Deleted` entry holding its last state.
 */
class DeleteWriter
{
    public function __construct(private RecordMasterHistory $recordHistory) {}

    public function __invoke(Writer $writer, ?int $userId = null): void
    {
        DB::transaction(function () use ($writer, $userId): void {
            $previous = RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($writer));

            $writer->delete();

            ($this->recordHistory)($writer, MasterChangeType::Deleted, previous: $previous, userId: $userId);
        });
    }
}
