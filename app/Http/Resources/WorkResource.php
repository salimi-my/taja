<?php

namespace App\Http\Resources;

use App\Models\Work;
use App\Models\WorkParty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Work
 */
class WorkResource extends JsonResource
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
            'tap_number' => $this->tap_number,
            'macp_work_id' => $this->macp_work_id,
            'english_title' => $this->english_title,
            'other_title' => $this->other_title,
            'title' => $this->displayTitle(),
            'iswc' => $this->iswc,
            'work_type' => $this->work_type,
            'genre' => $this->genre,
            'language' => $this->language,
            'registration_status' => $this->registration_status->value,
            'registration_status_label' => $this->registration_status->label(),
            'conflict_status' => $this->conflict_status->value,
            'conflict_status_label' => $this->conflict_status->label(),
            'is_public_domain' => $this->is_public_domain,
            'registration_date' => $this->registration_date?->toDateString(),
            'parties_count' => $this->whenLoaded('currentParties', fn (): int => $this->currentParties->count()),
            'contributors' => $this->whenLoaded('currentParties', fn () => $this->currentParties
                ->map(fn (WorkParty $party): array => [
                    'name' => $party->contributor_name,
                    'role' => $party->role->value,
                ])
                ->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
