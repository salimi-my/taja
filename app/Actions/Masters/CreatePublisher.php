<?php

namespace App\Actions\Masters;

use App\Enums\MasterChangeType;
use App\Models\Publisher;
use Illuminate\Support\Facades\DB;

/**
 * Creates a publisher and logs a `Created` entry.
 */
class CreatePublisher
{
    public function __construct(private RecordMasterHistory $recordHistory) {}

    /**
     * @param  array<string, mixed>  $data  The publisher fields.
     */
    public function __invoke(array $data, ?int $userId = null): Publisher
    {
        return DB::transaction(function () use ($data, $userId): Publisher {
            $publisher = Publisher::create($data);

            ($this->recordHistory)(
                $publisher,
                MasterChangeType::Created,
                new: RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($publisher)),
                userId: $userId,
            );

            return $publisher;
        });
    }
}
