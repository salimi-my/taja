<?php

namespace App\Http\Resources;

use App\Actions\Masters\SummarizeMasterHistory;
use App\Models\MasterHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MasterHistory
 */
class MasterHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'change_type' => $this->change_type->value,
            'change_type_label' => $this->change_type->label(),
            'summary' => app(SummarizeMasterHistory::class)($this->resource),
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'user_name' => $this->user?->name,
            'user_avatar_url' => $this->user?->avatar_url,
            'csv_import_id' => $this->csv_import_id,
            'remarks' => $this->remarks,
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'code' => $this->subject_code,
                'name' => $this->subject_name,
                'is_deleted' => $this->whenLoaded('subject', fn (): bool => $this->subject === null || $this->subject->trashed(), true),
            ],
            'previous_snapshot' => $this->previous_snapshot,
            'new_snapshot' => $this->new_snapshot,
        ];
    }
}
