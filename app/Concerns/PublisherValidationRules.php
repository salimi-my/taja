<?php

namespace App\Concerns;

use App\Casts\IdentityNumber;
use App\Casts\PhoneNumber;
use App\Enums\MasterStatus;
use App\Enums\RightsType;
use App\Models\Publisher;
use App\Rules\InternationalPhone;
use App\Rules\IpiNameNumber;
use App\Rules\Percent;
use App\Rules\TerritoryList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait PublisherValidationRules
{
    /**
     * Get the validation rules used to validate a publisher.
     *
     * Soft-deleted publishers keep their unique values reserved, so the unique
     * rules deliberately include them.
     *
     * It is static so the CSV import can reuse the exact same rules as the form.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function publisherRules(?int $publisherId = null): array
    {
        $unique = fn (string $column) => $publisherId === null
            ? Rule::unique(Publisher::class, $column)
            : Rule::unique(Publisher::class, $column)->ignore($publisherId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'rights_types_granted' => ['required', 'array', 'min:1'],
            'rights_types_granted.*' => ['string', Rule::enum(RightsType::class)],
            'ipi_base_number' => ['nullable', 'string', 'max:50'],
            'ipi_name_number' => ['nullable', new IpiNameNumber, $unique('ipi_name_number')],
            'company_registration_number' => ['required', 'string', 'max:255', $unique('company_registration_number')],
            'registration_address' => ['required', 'string', 'max:1000'],
            'official_email' => ['required', 'string', 'email', 'max:255'],
            'incorporation_date' => ['nullable', 'date_format:Y-m-d'],
            'society_cmo' => ['required', 'string', 'max:255'],
            'society_number' => ['nullable', 'string', 'max:255'],
            'tin_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(MasterStatus::class)],
            'agreement_start_date' => ['required', 'date_format:Y-m-d'],
            'agreement_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:agreement_start_date'],
            'auto_renewal' => ['nullable', 'boolean'],
            'pre_term' => ['nullable', 'boolean'],
            'post_term' => ['nullable', 'string', 'max:255'],
            'territories_granted' => ['required', new TerritoryList(mustExist: true)],
            'bank_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'client_account_number' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
            'bank_address' => ['nullable', 'string', 'max:1000'],
            'person_in_charge_name' => ['nullable', 'string', 'max:255'],
            'person_in_charge_title' => ['nullable', 'string', 'max:255'],
            'person_in_charge_nric' => ['nullable', 'string', 'max:255'],
            'person_in_charge_mobile_no' => ['nullable', 'string', new InternationalPhone],
            'person_in_charge_email' => ['nullable', 'string', 'email', 'max:255'],
            'taja_percentage_deal' => ['nullable', new Percent],
            'client_percentage_deal' => ['nullable', new Percent],
            'source_system' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Turn the territory text typed in the form (`MY, SG`) into a list, and
     * store the NRIC and mobile number without dashes.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('person_in_charge_nric')) {
            $this->merge(['person_in_charge_nric' => IdentityNumber::normalize($this->input('person_in_charge_nric'))]);
        }

        if ($this->has('person_in_charge_mobile_no')) {
            $this->merge(['person_in_charge_mobile_no' => PhoneNumber::normalize($this->input('person_in_charge_mobile_no'))]);
        }

        if ($this->has('territories_granted')) {
            $this->merge([
                'territories_granted' => TerritoryList::parse($this->input('territories_granted')),
            ]);
        }
    }

    /**
     * When both deal percentages are given they must total 100.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $taja = $this->input('taja_percentage_deal');
                $client = $this->input('client_percentage_deal');

                if ($validator->errors()->hasAny(['taja_percentage_deal', 'client_percentage_deal'])) {
                    return;
                }

                if (filled($taja) && filled($client) && abs((float) $taja + (float) $client - 100) > 0.001) {
                    $validator->errors()->add(
                        'client_percentage_deal',
                        'The Taja and client percentage deals must total 100.',
                    );
                }
            },
        ];
    }
}
