<?php

namespace App\Http\Requests\Works;

use Illuminate\Contracts\Validation\ValidationRule;

class RemoveWorkPartyRequest extends WorkPartyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
