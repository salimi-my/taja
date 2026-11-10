<?php

namespace App\Actions\Masters;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\MasterChangeType;
use App\Models\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Updates a writer and replaces its pseudonyms, then logs an `Updated` entry
 * holding what changed. Nothing is logged when nothing changed.
 */
class UpdateWriter
{
    public function __construct(
        private RecordMasterHistory $recordHistory,
        private SyncWriterAliases $syncAliases,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The writer fields, plus optional `aliases`.
     */
    public function __invoke(Writer $writer, array $data, ?int $userId = null): Writer
    {
        return DB::transaction(function () use ($writer, $data, $userId): Writer {
            $previous = RecordMasterHistory::snapshot($writer);

            $writer->update(Arr::except($data, 'aliases'));

            ($this->syncAliases)($writer, $data['aliases'] ?? []);

            $changes = RecordRightsHistory::changedAttributes($previous, RecordMasterHistory::snapshot($writer->refresh()));

            if ($changes['new'] !== []) {
                ($this->recordHistory)(
                    $writer,
                    MasterChangeType::Updated,
                    previous: $changes['previous'],
                    new: $changes['new'],
                    userId: $userId,
                );
            }

            return $writer;
        });
    }
}
