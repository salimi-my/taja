<?php

namespace App\Actions\Masters;

use App\Actions\Works\RecordRightsHistory;
use App\Enums\MasterChangeType;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Updates a writer and replaces its pseudonyms, then logs an `Updated` entry
 * holding what changed. Nothing is logged when nothing changed. A rename also
 * renames the party rows that still carry the old name.
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

            $previousName = $writer->name;

            $writer->update(Arr::except($data, 'aliases'));

            if ($writer->name !== $previousName) {
                WorkParty::query()
                    ->where('writer_id', $writer->id)
                    ->where('contributor_name', $previousName)
                    ->update(['contributor_name' => $writer->name]);
            }

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
