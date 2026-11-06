<?php

namespace App\Http\Requests\Publishers;

use App\Concerns\PublisherValidationRules;
use App\Models\Publisher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePublisherRequest extends FormRequest
{
    use PublisherValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Publisher::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->publisherRules();
    }
}
