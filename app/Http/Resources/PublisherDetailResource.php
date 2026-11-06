<?php

namespace App\Http\Resources;

use App\Models\Publisher;
use Illuminate\Http\Request;

/**
 * @mixin Publisher
 */
class PublisherDetailResource extends PublisherResource
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
            'company_registration_number' => $this->company_registration_number,
            'registration_address' => $this->registration_address,
            'official_email' => $this->official_email,
            'incorporation_date' => $this->incorporation_date?->toDateString(),
            'society_number' => $this->society_number,
            'tin_number' => $this->tin_number,
            'auto_renewal' => $this->auto_renewal,
            'pre_term' => $this->pre_term,
            'post_term' => $this->post_term,
            'bank_holder_name' => $this->bank_holder_name,
            'bank_name' => $this->bank_name,
            'client_account_number' => $this->client_account_number,
            'swift_code' => $this->swift_code,
            'bank_address' => $this->bank_address,
            'person_in_charge_name' => $this->person_in_charge_name,
            'person_in_charge_title' => $this->person_in_charge_title,
            'person_in_charge_nric' => $this->person_in_charge_nric,
            'person_in_charge_mobile_no' => $this->person_in_charge_mobile_no,
            'person_in_charge_email' => $this->person_in_charge_email,
            'taja_percentage_deal' => $this->taja_percentage_deal,
            'client_percentage_deal' => $this->client_percentage_deal,
            'source_system' => $this->source_system,
            'notes' => $this->notes,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
