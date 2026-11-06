<?php

namespace App\Http\Requests\Works;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class ChangeWorkPartyRequest extends WorkPartyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->partyFieldRules(),
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkRoleMatchesParty($validator)];
    }
}
