<?php

namespace App\Http\Requests\Imports;

use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use App\Models\CsvImport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvImportIndexRequest extends FormRequest
{
    /**
     * The columns the import history can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = [
        'created_at',
        'original_filename',
        'type',
        'status',
        'total_rows',
    ];

    /**
     * The page sizes the import history can be shown with.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', CsvImport::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', Rule::enum(CsvImportType::class)],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::enum(CsvImportStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }
}
