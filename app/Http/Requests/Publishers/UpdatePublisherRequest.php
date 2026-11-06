<?php

namespace App\Http\Requests\Publishers;

use App\Concerns\PublisherValidationRules;
use App\Models\Publisher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublisherRequest extends FormRequest
{
    use PublisherValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Publisher $publisher */
        $publisher = $this->route('publisher');

        return $this->user()->can('update', $publisher);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Publisher $publisher */
        $publisher = $this->route('publisher');

        return $this->publisherRules($publisher->id);
    }
}
