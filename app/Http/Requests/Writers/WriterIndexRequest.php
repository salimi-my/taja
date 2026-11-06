<?php

namespace App\Http\Requests\Writers;

use App\Enums\MasterStatus;
use App\Models\Writer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WriterIndexRequest extends FormRequest
{
    /**
     * The columns the writer list can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = [
        'name',
        'writer_code',
        'cmo_society',
        'status',
        'agreement_start_date',
        'created_at',
    ];

    /**
     * The page sizes the writer list can be shown with.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Writer::class);
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
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::enum(MasterStatus::class)],
            'societies' => ['nullable', 'array'],
            'societies.*' => ['string', 'max:255'],
            'publishers' => ['nullable', 'array'],
            'publishers.*' => ['integer'],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }
}
