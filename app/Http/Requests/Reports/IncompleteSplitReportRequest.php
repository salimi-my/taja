<?php

namespace App\Http\Requests\Reports;

use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncompleteSplitReportRequest extends FormRequest
{
    /**
     * The columns the report can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['tap_number', 'macp_work_id', 'english_title', 'registration_status'];

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
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * The filters of the report, as the screen and the CSV export both use them.
     *
     * @return array{search: string, sort: string, direction: 'asc'|'desc', per_page: int}
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->toString(),
            'sort' => $this->string('sort', 'tap_number')->toString(),
            'direction' => $this->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc',
            'per_page' => $this->integer('per_page', 10),
        ];
    }
}
