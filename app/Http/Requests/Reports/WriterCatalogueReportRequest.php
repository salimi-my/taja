<?php

namespace App\Http\Requests\Reports;

use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WriterCatalogueReportRequest extends FormRequest
{
    /**
     * The page sizes the report can be shown with.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Work::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'writer_id' => ['nullable', 'integer', 'exists:writers,id'],
            'include_ended' => ['nullable', 'boolean'],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * The filters of the report, as the screen and the CSV export both use them.
     *
     * @return array{writer_id: int|null, include_ended: bool, per_page: int}
     */
    public function filters(): array
    {
        return [
            'writer_id' => $this->filled('writer_id') ? $this->integer('writer_id') : null,
            'include_ended' => $this->boolean('include_ended'),
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
