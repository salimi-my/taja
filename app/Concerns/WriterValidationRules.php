<?php

namespace App\Concerns;

use App\Casts\IdentityNumber;
use App\Casts\PhoneNumber;
use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RightsType;
use App\Models\Writer;
use App\Rules\InternationalPhone;
use App\Rules\IpiNameNumber;
use App\Rules\Percent;
use App\Rules\TerritoryList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait WriterValidationRules
{
    /**
     * The most pseudonyms a writer can have.
     */
    public const MAX_ALIASES = 4;

    /**
     * Get the validation rules used to validate a writer.
     *
     * Soft-deleted writers keep their unique values reserved, so the unique
     * rules deliberately include them. A duplicate NRIC is allowed on purpose.
     *
     * It is static so the CSV import can reuse the exact same rules as the form.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    public static function writerRules(?int $writerId = null): array
    {
        $unique = fn (string $column) => $writerId === null
            ? Rule::unique(Writer::class, $column)
            : Rule::unique(Writer::class, $column)->ignore($writerId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'default_role' => ['nullable', Rule::enum(PartyRole::class), function (string $attribute, mixed $value, Closure $fail): void {
                if (PartyRole::tryFrom((string) $value)?->isWriterRole() === false) {
                    $fail('The default role must be a writer role (C, A, CA, AR or SA).');
                }
            }],
            'ipi_base_number' => ['nullable', 'string', 'max:50'],
            'ipi_name_number' => ['nullable', new IpiNameNumber, $unique('ipi_name_number')],
            'nric' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'date_of_death' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_of_birth'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'cmo_society' => ['required', 'string', 'max:255'],
            'publisher_id' => ['nullable', 'integer', Rule::exists('publishers', 'id')->whereNull('deleted_at')],
            'controlled_territories' => ['nullable', new TerritoryList(mustExist: true)],
            'rights_types_controlled' => ['nullable', 'array'],
            'rights_types_controlled.*' => ['string', Rule::enum(RightsType::class)],
            'tin_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', new InternationalPhone],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(MasterStatus::class)],
            'agreement_start_date' => ['required', 'date_format:Y-m-d'],
            'agreement_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:agreement_start_date'],
            'auto_renewal' => ['nullable', 'boolean'],
            'pre_term' => ['nullable', 'boolean'],
            'post_term' => ['nullable', 'string', 'max:255'],
            'taja_percentage_deal' => ['nullable', new Percent],
            'writer_percentage_deal' => ['nullable', new Percent],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
            'bank_address' => ['nullable', 'string', 'max:1000'],
            'heir_name' => ['nullable', 'string', 'max:255'],
            'heir_nric' => ['nullable', 'string', 'max:255'],
            'heir_relationship' => ['nullable', 'string', 'max:255'],
            'heir_mobile_number' => ['nullable', 'string', new InternationalPhone],
            'heir_email' => ['nullable', 'string', 'email', 'max:255'],
            'source_system' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'aliases' => ['nullable', 'array', 'max:'.self::MAX_ALIASES],
            'aliases.*.name' => ['required', 'string', 'max:255'],
            'aliases.*.ipi_base_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get readable names for the repeated pseudonym and rights type fields, so
     * errors do not show raw keys such as "aliases.0.name".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'aliases.*.name' => 'pseudonym name',
            'aliases.*.ipi_base_number' => 'pseudonym IPI base number',
            'rights_types_controlled.*' => 'rights type',
        ];
    }

    /**
     * Turn the territory text typed in the form (`MY, SG`) into a list, drop
     * pseudonym rows that were left completely blank, and store NRIC and phone
     * numbers without dashes.
     */
    protected function prepareForValidation(): void
    {
        foreach (['nric', 'heir_nric'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => IdentityNumber::normalize($this->input($field))]);
            }
        }

        foreach (['phone', 'heir_mobile_number'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => PhoneNumber::normalize($this->input($field))]);
            }
        }

        if ($this->has('controlled_territories')) {
            $territories = TerritoryList::parse($this->input('controlled_territories'));

            $this->merge(['controlled_territories' => $territories === [] ? null : $territories]);
        }

        if (! $this->has('rights_types_controlled')) {
            $this->merge(['rights_types_controlled' => null]);
        }

        if (is_array($this->input('aliases'))) {
            $aliases = array_filter(
                $this->input('aliases'),
                fn (mixed $alias): bool => ! is_array($alias)
                    || filled($alias['name'] ?? null)
                    || filled($alias['ipi_base_number'] ?? null),
            );

            $this->merge(['aliases' => array_values($aliases)]);
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
                $writer = $this->input('writer_percentage_deal');

                if ($validator->errors()->hasAny(['taja_percentage_deal', 'writer_percentage_deal'])) {
                    return;
                }

                if (filled($taja) && filled($writer) && abs((float) $taja + (float) $writer - 100) > 0.001) {
                    $validator->errors()->add(
                        'writer_percentage_deal',
                        'The Taja and writer percentage deals must total 100.',
                    );
                }
            },
        ];
    }
}
