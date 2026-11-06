<?php

namespace App\Http\Resources;

use App\Enums\RightsType;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Publisher
 */
class PublisherResource extends JsonResource
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
            'publisher_code' => $this->publisher_code,
            'name' => $this->name,
            'ipi_name_number' => $this->ipi_name_number,
            'society_cmo' => $this->society_cmo,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'rights_types_granted' => $this->rights_types_granted->map(fn (RightsType $type): string => $type->value)->values(),
            'territories_granted' => $this->territories_granted,
            'agreement_start_date' => $this->agreement_start_date->toDateString(),
            'agreement_end_date' => $this->agreement_end_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
