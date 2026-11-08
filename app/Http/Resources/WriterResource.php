<?php

namespace App\Http\Resources;

use App\Models\Writer;
use App\Models\WriterAlias;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Writer
 */
class WriterResource extends JsonResource
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
            'writer_code' => $this->writer_code,
            'name' => $this->name,
            'ipi_name_number' => $this->ipi_name_number,
            'cmo_society' => $this->cmo_society,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'default_role' => $this->default_role?->value,
            'default_role_label' => $this->default_role?->displayLabel(),
            'publisher' => $this->publisher === null ? null : [
                'id' => $this->publisher->id,
                'name' => $this->publisher->name,
                'publisher_code' => $this->publisher->publisher_code,
            ],
            'aliases' => $this->aliases->map(fn (WriterAlias $alias): array => [
                'id' => $alias->id,
                'name' => $alias->name,
                'ipi_base_number' => $alias->ipi_base_number,
                'position' => $alias->position,
            ])->values(),
            'agreement_start_date' => $this->agreement_start_date->toDateString(),
            'agreement_end_date' => $this->agreement_end_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
