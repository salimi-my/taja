<?php

namespace App\Actions\Masters;

use App\Enums\MasterChangeType;
use App\Models\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates a writer with its pseudonyms and logs a `Created` entry.
 */
class CreateWriter
{
    public function __construct(
        private RecordMasterHistory $recordHistory,
        private SyncWriterAliases $syncAliases,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The writer fields, plus optional `aliases`.
     */
    public function __invoke(array $data, ?int $userId = null): Writer
    {
        return DB::transaction(function () use ($data, $userId): Writer {
            $writer = Writer::create(Arr::except($data, 'aliases'));

            ($this->syncAliases)($writer, $data['aliases'] ?? []);

            ($this->recordHistory)(
                $writer,
                MasterChangeType::Created,
                new: RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($writer)),
                userId: $userId,
            );

            return $writer;
        });
    }
}
