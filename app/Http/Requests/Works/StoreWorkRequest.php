<?php

namespace App\Http\Requests\Works;

use App\Actions\Works\ResolveInitialEffectiveDate;
use App\Actions\Works\ValidatePartyShares;
use App\Concerns\WorkValidationRules;
use App\Enums\PartyRole;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\Writer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkRequest extends FormRequest
{
    use WorkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Work::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...$this->workRules(), ...$this->partyRules()];
    }

    /**
     * Check that each party matches its role and that the shares add up
     * (business rules 4 and 5).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $parties = $this->input('parties', []);

                $this->checkPartyTypes($validator, $parties);

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->partyErrors($parties) as $error) {
                    $validator->errors()->add($error['field'], $error['message']);
                }
            },
        ];
    }

    /**
     * A writer role needs a writer and a publisher role needs a publisher.
     *
     * @param  list<array<string, mixed>>  $parties
     */
    private function checkPartyTypes(Validator $validator, array $parties): void
    {
        foreach ($parties as $index => $party) {
            foreach ($this->partyTypeErrors($party, "parties.{$index}.") as $error) {
                $validator->errors()->add($error['field'], $error['message']);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $parties
     * @return list<array{field: string, message: string}>
     */
    private function partyErrors(array $parties): array
    {
        $writers = Writer::query()->findMany(array_filter(array_column($parties, 'writer_id')))->keyBy('id');
        $publishers = Publisher::query()->findMany(array_filter(array_column($parties, 'publisher_id')))->keyBy('id');
        $resolveEffectiveDate = app(ResolveInitialEffectiveDate::class);

        $rows = array_map(function (array $party) use ($writers, $publishers, $resolveEffectiveDate): array {
            $master = filled($party['writer_id'] ?? null)
                ? $writers->get($party['writer_id'])
                : $publishers->get($party['publisher_id']);

            return [
                'writer_id' => filled($party['writer_id'] ?? null) ? (int) $party['writer_id'] : null,
                'publisher_id' => filled($party['publisher_id'] ?? null) ? (int) $party['publisher_id'] : null,
                'role' => PartyRole::from($party['role']),
                'performance_share' => $party['performance_share'] ?? null,
                'mechanical_share' => $party['mechanical_share'] ?? null,
                'sync_share' => $party['sync_share'] ?? null,
                'lyrics_share' => $party['lyrics_share'] ?? null,
                'territories_controlled' => $party['territories_controlled'] ?? [],
                'effective_start_date' => $resolveEffectiveDate($this->input('administration_start_date'), $master),
                'effective_end_date' => null,
            ];
        }, $parties);

        return app(ValidatePartyShares::class)($rows);
    }
}
