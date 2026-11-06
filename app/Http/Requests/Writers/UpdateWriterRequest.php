<?php

namespace App\Http\Requests\Writers;

use App\Concerns\WriterValidationRules;
use App\Models\Writer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWriterRequest extends FormRequest
{
    use WriterValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Writer $writer */
        $writer = $this->route('writer');

        return $this->user()->can('update', $writer);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Writer $writer */
        $writer = $this->route('writer');

        return $this->writerRules($writer->id);
    }
}
