<?php

namespace App\Http\Requests\Reports;

use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use App\Models\Work;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SongOwnershipReportRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'work_id' => ['nullable', 'integer', 'exists:works,id'],
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'registration_statuses' => ['nullable', 'array'],
            'registration_statuses.*' => ['string', Rule::enum(RegistrationStatus::class)],
            'conflict_statuses' => ['nullable', 'array'],
            'conflict_statuses.*' => ['string', Rule::enum(ConflictStatus::class)],
            'controlled' => ['nullable', 'array'],
            'controlled.*' => ['string', Rule::in(['yes', 'no'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * The filters of the report, as the screen and the CSV export both use them.
     *
     * @return array{search: string, work_id: int|null, as_of: string|null, registration_statuses: list<string>, conflict_statuses: list<string>, controlled: list<string>, per_page: int}
     */
    public function filters(): array
    {
        return [
            'search' => $this->string('search')->trim()->toString(),
            'work_id' => $this->filled('work_id') ? $this->integer('work_id') : null,
            'as_of' => $this->filled('as_of') ? $this->string('as_of')->toString() : null,
            'registration_statuses' => $this->stringList('registration_statuses'),
            'conflict_statuses' => $this->stringList('conflict_statuses'),
            'controlled' => $this->stringList('controlled'),
            'per_page' => $this->integer('per_page', 10),
        ];
    }

    /**
     * A validated array input as a list of strings.
     *
     * @return list<string>
     */
    private function stringList(string $key): array
    {
        return array_values(array_map(strval(...), $this->array($key)));
    }
}
