<?php

namespace App\Http\Resources;

use App\Actions\Reports\ShareTotals;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A work with the party rows loaded on it and their share totals.
 *
 * @mixin Work
 */
class WorkOwnershipResource extends JsonResource
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
            'title' => $this->displayTitle(),
            'registration_status' => $this->registration_status->value,
            'registration_status_label' => $this->registration_status->label(),
            'conflict_status' => $this->conflict_status->value,
            'conflict_status_label' => $this->conflict_status->label(),
            'parties' => WorkPartyResource::collection($this->parties),
            'totals' => app(ShareTotals::class)($this->parties),
        ];
    }
}
