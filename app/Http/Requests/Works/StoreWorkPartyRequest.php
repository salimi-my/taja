<?php

namespace App\Http\Requests\Works;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class StoreWorkPartyRequest extends WorkPartyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->partyIdentityRules(),
            ...$this->partyFieldRules(),
            'effective_start_date' => ['required', 'date_format:Y-m-d'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * A writer role needs a writer and a publisher role needs a publisher.
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

                foreach ($this->partyTypeErrors($this->all()) as $error) {
                    $validator->errors()->add($error['field'], $error['message']);
                }
            },
        ];
    }
}
