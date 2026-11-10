<?php

namespace App\Http\Requests\Reports;

use App\Enums\MasterChangeType;
use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterHistoryReportRequest extends FormRequest
{
    /**
     * The columns the report can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['recorded_at', 'subject_name'];

    /**
     * The kinds of record the report can be narrowed to.
     *
     * @var list<string>
     */
    public const SUBJECT_TYPES = ['writer', 'publisher'];

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
        return $this->user()->can('viewAny', Writer::class)
            && $this->user()->can('viewAny', Publisher::class);
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
            'types.*' => ['string', Rule::in(self::SUBJECT_TYPES)],
            'change_types' => ['nullable', 'array'],
            'change_types.*' => ['string', Rule::enum(MasterChangeType::class)],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer'],
            'recorded_from' => ['nullable', 'date_format:Y-m-d'],
            'recorded_to' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * The filters of the report, as the screen and the CSV export both use them.
     *
     * @return array{
     *     search: string,
     *     types: list<string>,
     *     change_types: list<string>,
     *     users: list<string>,
     *     recorded_from: string,
     *     recorded_to: string,
     *     sort: string,
     *     direction: 'asc'|'desc',
     *     per_page: int,
     * }
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->toString(),
            'types' => array_values(array_filter($this->array('types'), is_string(...))),
            'change_types' => array_values(array_filter($this->array('change_types'), is_string(...))),
            'users' => array_values(array_filter($this->array('users'), is_string(...))),
            'recorded_from' => $this->string('recorded_from')->toString(),
            'recorded_to' => $this->string('recorded_to')->toString(),
            'sort' => $this->string('sort', 'recorded_at')->toString(),
            'direction' => $this->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc',
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
