<?php

namespace App\Http\Requests\Works;

use App\Concerns\WorkValidationRules;
use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRequest extends FormRequest
{
    use WorkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Work $work */
        $work = $this->route('work');

        return $this->user()->can('update', $work);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Parties are not accepted here: rights data is never overwritten.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Work $work */
        $work = $this->route('work');

        return [
            ...$this->workRules($work->id),
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
