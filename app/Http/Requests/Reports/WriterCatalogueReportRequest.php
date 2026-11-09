<?php

namespace App\Http\Requests\Reports;

use App\Enums\ConflictStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
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
            'search' => ['nullable', 'string', 'max:100'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in(self::writerRoles())],
            'registration_statuses' => ['nullable', 'array'],
            'registration_statuses.*' => ['string', Rule::enum(RegistrationStatus::class)],
            'conflict_statuses' => ['nullable', 'array'],
            'conflict_statuses.*' => ['string', Rule::enum(ConflictStatus::class)],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * The filters of the report, as the screen and the CSV export both use them.
     *
     * @return array{writer_id: int|null, include_ended: bool, search: string, roles: list<string>, registration_statuses: list<string>, conflict_statuses: list<string>, per_page: int}
     */
    public function filters(): array
    {
        return [
            'writer_id' => $this->filled('writer_id') ? $this->integer('writer_id') : null,
            'include_ended' => $this->boolean('include_ended'),
            'search' => $this->string('search')->trim()->toString(),
            'roles' => $this->stringList('roles'),
            'registration_statuses' => $this->stringList('registration_statuses'),
            'conflict_statuses' => $this->stringList('conflict_statuses'),
            'per_page' => $this->integer('per_page', 10),
        ];
    }

    /**
     * The party roles of a writer, which are the roles a writer can hold on a work.
     *
     * @return list<string>
     */
    public static function writerRoles(): array
    {
        return array_values(array_map(
            fn (PartyRole $role): string => $role->value,
            array_filter(PartyRole::cases(), fn (PartyRole $role): bool => $role->isWriterRole()),
        ));
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
