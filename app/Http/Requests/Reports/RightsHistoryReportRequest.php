<?php

namespace App\Http\Requests\Reports;

use App\Enums\RightsChangeType;
use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RightsHistoryReportRequest extends FormRequest
{
    /**
     * The columns the report can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['effective_date', 'recorded_at'];

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
            'search' => ['nullable', 'string', 'max:100'],
            'work_id' => ['nullable', 'integer', 'exists:works,id'],
            'change_types' => ['nullable', 'array'],
            'change_types.*' => ['string', Rule::enum(RightsChangeType::class)],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer'],
            'effective_from' => ['nullable', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d'],
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
     *     work_id: int|null,
     *     change_types: list<string>,
     *     users: list<string>,
     *     effective_from: string,
     *     effective_to: string,
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
            'work_id' => $this->filled('work_id') ? $this->integer('work_id') : null,
            'change_types' => array_values(array_filter($this->array('change_types'), is_string(...))),
            'users' => array_values(array_filter($this->array('users'), is_string(...))),
            'effective_from' => $this->string('effective_from')->toString(),
            'effective_to' => $this->string('effective_to')->toString(),
            'recorded_from' => $this->string('recorded_from')->toString(),
            'recorded_to' => $this->string('recorded_to')->toString(),
            'sort' => $this->string('sort', 'effective_date')->toString(),
            'direction' => $this->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc',
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
