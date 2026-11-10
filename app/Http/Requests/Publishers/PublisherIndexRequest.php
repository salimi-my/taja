<?php

namespace App\Http\Requests\Publishers;

use App\Enums\MasterStatus;
use App\Enums\RightsType;
use App\Models\Publisher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublisherIndexRequest extends FormRequest
{
    /**
     * The columns the publisher list can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = [
        'name',
        'publisher_code',
        'society_cmo',
        'status',
        'agreement_start_date',
        'created_at',
    ];

    /**
     * The page sizes the publisher list can be shown with.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Publisher::class);
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
            'rights_types' => ['nullable', 'array'],
            'rights_types.*' => ['string', Rule::enum(RightsType::class)],
            'territories' => ['nullable', 'array'],
            'territories.*' => ['string', 'max:2'],
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
     *     rights_types: list<string>,
     *     territories: list<string>,
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
            'rights_types' => array_values(array_filter($this->array('rights_types'), is_string(...))),
            'territories' => array_map(strtoupper(...), array_values(array_filter($this->array('territories'), is_string(...)))),
            'sort' => $this->string('sort', 'created_at')->toString(),
            'direction' => $this->string('direction', 'desc')->toString(),
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
