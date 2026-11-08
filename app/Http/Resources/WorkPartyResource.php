<?php

namespace App\Http\Resources;

use App\Models\WorkParty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkParty
 */
class WorkPartyResource extends JsonResource
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
            'work_id' => $this->work_id,
            'role' => $this->role->value,
            'role_label' => $this->role->displayLabel(),
            'party_type' => $this->writer_id !== null ? 'writer' : 'publisher',
            'party_id' => $this->writer_id ?? $this->publisher_id,
            'party_code' => $this->writer_id !== null
                ? $this->writer?->writer_code
                : $this->publisher?->publisher_code,
            'contributor_name' => $this->contributor_name,
            'performance_share' => $this->performance_share,
            'mechanical_share' => $this->mechanical_share,
            'sync_share' => $this->sync_share,
            'lyrics_share' => $this->lyrics_share,
            'is_controlled' => $this->is_controlled,
            'territories_controlled' => $this->territories_controlled,
            'territory_exclusions' => $this->territory_exclusions ?? [],
            'effective_start_date' => $this->effective_start_date->toDateString(),
            'effective_end_date' => $this->effective_end_date?->toDateString(),
            'superseded_at' => $this->superseded_at?->toIso8601String(),
            'work' => $this->whenLoaded('work', fn (): array => [
                'id' => $this->work->id,
                'tap_number' => $this->work->tap_number,
                'macp_work_id' => $this->work->macp_work_id,
                'title' => $this->work->displayTitle(),
                'registration_status' => $this->work->registration_status->value,
                'registration_status_label' => $this->work->registration_status->label(),
                'conflict_status' => $this->work->conflict_status->value,
                'conflict_status_label' => $this->work->conflict_status->label(),
            ]),
        ];
    }
}
