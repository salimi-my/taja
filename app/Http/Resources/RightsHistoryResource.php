<?php

namespace App\Http\Resources;

use App\Actions\Works\SummarizeRightsHistory;
use App\Models\RightsHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RightsHistory
 */
class RightsHistoryResource extends JsonResource
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
            'summary' => app(SummarizeRightsHistory::class)($this->resource),
            'effective_date' => $this->effective_date->toDateString(),
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'user_name' => $this->user?->name,
            'user_avatar_url' => $this->user?->avatar_url,
            'work_party_id' => $this->work_party_id,
            'csv_import_id' => $this->csv_import_id,
            'remarks' => $this->remarks,
            'work' => $this->whenLoaded('work', fn (): array => [
                'id' => $this->work->id,
                'tap_number' => $this->work->tap_number,
                'macp_work_id' => $this->work->macp_work_id,
                'title' => $this->work->displayTitle(),
            ]),
            'previous_snapshot' => $this->previous_snapshot,
            'new_snapshot' => $this->new_snapshot,
        ];
    }
}
