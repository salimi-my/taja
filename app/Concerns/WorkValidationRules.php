<?php

namespace App\Concerns;

use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use App\Models\Work;
use App\Rules\DurationHms;
use App\Rules\Isrc;
use App\Rules\Iswc;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait WorkValidationRules
{
    use WorkPartyValidationRules;

    /**
     * Get the validation rules for the work-level fields and the ISRC list.
     *
     * A soft-deleted work keeps its MACP Work ID reserved, so the unique rule
     * deliberately includes soft-deleted rows.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    public static function workRules(?int $workId = null): array
    {
        $unique = $workId === null
            ? Rule::unique(Work::class, 'macp_work_id')
            : Rule::unique(Work::class, 'macp_work_id')->ignore($workId);

        return [
            'macp_work_id' => ['required', 'string', 'max:255', $unique],
            'english_title' => ['nullable', 'string', 'max:255', 'required_without:other_title'],
            'other_title' => ['nullable', 'string', 'max:255', 'required_without:english_title'],
            'iswc' => ['nullable', new Iswc],
            'work_type' => ['nullable', 'string', 'max:50'],
            'genre' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', new DurationHms],
            'artist_name' => ['nullable', 'string', 'max:255'],
            'original_work_title' => ['nullable', 'string', 'max:255'],
            'sub_title_work' => ['nullable', 'string', 'max:255'],
            'society_no' => ['nullable', 'string', 'max:255'],
            'administration_start_date' => ['nullable', 'date_format:Y-m-d'],
            'administration_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:administration_start_date'],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
            'registration_status' => ['required', Rule::enum(RegistrationStatus::class)],
            'is_public_domain' => ['required', 'boolean'],
            'song_link' => ['nullable', 'url', 'max:2048'],
            'remark' => ['nullable', 'string', 'max:5000'],
            'release_date' => ['nullable', 'date_format:Y-m-d'],
            'compass_work_internal_no' => ['nullable', 'string', 'max:255'],
            'compass_work_type' => ['nullable', 'string', 'max:255'],
            'compass_start_date' => ['nullable', 'date_format:Y-m-d'],
            'wami_work_internal_no' => ['nullable', 'string', 'max:255'],
            'wami_work_type' => ['nullable', 'string', 'max:255'],
            'conflict_status' => ['required', Rule::enum(ConflictStatus::class)],
            'conflict_notes' => ['nullable', 'string', 'max:5000'],
            'isrcs' => ['nullable', 'array', 'max:50'],
            'isrcs.*' => ['string', new Isrc],
        ];
    }

    /**
     * Get the validation rules for the parties repeater (used when creating).
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    protected function partyRules(): array
    {
        $rules = ['parties' => ['nullable', 'array', 'max:50']];

        foreach ([...$this->partyFieldRules(), ...$this->partyIdentityRules()] as $field => $fieldRules) {
            $rules["parties.*.{$field}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Get readable names for the repeated ISRC and party fields, so errors read
     * "The performance share field is required" and not "parties.0.performance_share".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $names = ['isrcs.*' => 'ISRC'];

        foreach ([...array_keys($this->partyFieldRules()), ...array_keys($this->partyIdentityRules())] as $field) {
            $names["parties.*.{$field}"] = str_replace('_', ' ', Str::beforeLast($field, '_id'));
        }

        return $names;
    }

    /**
     * Tidy the form input: uppercase the work type, default the conflict
     * status, drop blank ISRC and party rows, and turn territory text into lists.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('work_type'))) {
            $this->merge(['work_type' => strtoupper(trim($this->input('work_type')))]);
        }

        if (blank($this->input('conflict_status'))) {
            $this->merge(['conflict_status' => ConflictStatus::NoConflict->value]);
        }

        $isrcs = is_array($this->input('isrcs')) ? $this->input('isrcs') : [];

        $this->merge([
            'isrcs' => array_values(array_filter(
                array_map(fn (mixed $isrc): mixed => is_string($isrc) ? trim($isrc) : $isrc, $isrcs),
                fn (mixed $isrc): bool => filled($isrc),
            )),
        ]);

        if (is_array($this->input('parties'))) {
            $this->merge(['parties' => $this->tidyParties($this->input('parties'))]);
        }
    }

    /**
     * Drop party rows left completely blank and parse their territory lists.
     *
     * @param  array<int|string, mixed>  $parties
     * @return list<mixed>
     */
    private function tidyParties(array $parties): array
    {
        $tidied = [];

        foreach ($parties as $party) {
            if (! is_array($party)) {
                $tidied[] = $party;

                continue;
            }

            $isBlank = blank($party['writer_id'] ?? null)
                && blank($party['publisher_id'] ?? null)
                && blank($party['performance_share'] ?? null)
                && blank($party['mechanical_share'] ?? null);

            if ($isBlank) {
                continue;
            }

            $party = $this->parsePartyTerritories($party);

            $tidied[] = $party;
        }

        return $tidied;
    }
}
