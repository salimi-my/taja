<?php

namespace App\Http\Requests\Works;

use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkIndexRequest extends FormRequest
{
    /**
     * The columns the work list can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = [
        'tap_number',
        'macp_work_id',
        'english_title',
        'registration_status',
        'registration_date',
        'created_at',
    ];

    /**
     * The page sizes the work list can be shown with.
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
            'registration_statuses' => ['nullable', 'array'],
            'registration_statuses.*' => ['string', Rule::enum(RegistrationStatus::class)],
            'conflict_statuses' => ['nullable', 'array'],
            'conflict_statuses.*' => ['string', Rule::enum(ConflictStatus::class)],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:255'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['string', 'max:255'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }
}
