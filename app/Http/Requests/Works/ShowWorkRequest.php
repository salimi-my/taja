<?php

namespace App\Http\Requests\Works;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowWorkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('work'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `as_of` shows the parties as they were in effect on a date, and
     * `history_page` pages the history tab.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'history_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
