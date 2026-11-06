<?php

namespace App\Http\Resources;

use App\Enums\RightsType;
use App\Models\Writer;
use Illuminate\Http\Request;

/**
 * @mixin Writer
 */
class WriterDetailResource extends WriterResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'ipi_base_number' => $this->ipi_base_number,
            'nric' => $this->nric,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'date_of_death' => $this->date_of_death?->toDateString(),
            'nationality' => $this->nationality,
            'publisher_id' => $this->publisher_id,
            'controlled_territories' => $this->controlled_territories ?? [],
            'rights_types_controlled' => ($this->rights_types_controlled ?? collect())
                ->map(fn (RightsType $type): string => $type->value)
                ->values(),
            'tin_number' => $this->tin_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'auto_renewal' => $this->auto_renewal,
            'pre_term' => $this->pre_term,
            'post_term' => $this->post_term,
            'taja_percentage_deal' => $this->taja_percentage_deal,
            'writer_percentage_deal' => $this->writer_percentage_deal,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'swift_code' => $this->swift_code,
            'bank_address' => $this->bank_address,
            'heir_name' => $this->heir_name,
            'heir_nric' => $this->heir_nric,
            'heir_relationship' => $this->heir_relationship,
            'heir_mobile_number' => $this->heir_mobile_number,
            'heir_email' => $this->heir_email,
            'source_system' => $this->source_system,
            'notes' => $this->notes,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
