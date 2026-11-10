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

    /**
     * The filters of the list, as the screen and the CSV export both use them.
     *
     * @return array{
     *     search: string,
     *     status: list<string>,
     *     societies: list<string>,
     *     publishers: list<string>,
     *     sort: string,
     *     direction: string,
     *     per_page: int,
     * }
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->toString(),
            'status' => array_values(array_filter($this->array('status'), is_string(...))),
            'societies' => array_values(array_filter($this->array('societies'), is_string(...))),
            'publishers' => array_map(strval(...), array_values(array_filter($this->array('publishers'), is_scalar(...)))),
            'sort' => $this->string('sort', 'created_at')->toString(),
            'direction' => $this->string('direction', 'desc')->toString(),
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
