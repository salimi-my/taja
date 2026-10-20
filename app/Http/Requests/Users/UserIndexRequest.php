<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    /**
     * The columns the user list can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['name', 'email', 'role', 'email_verified_at', 'created_at'];

    /**
     * The email verification statuses the user list can be filtered by.
     *
     * @var list<string>
     */
    public const STATUSES = ['verified', 'unverified'];

    /**
     * The page sizes the user list can be shown with.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
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
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::enum(UserRole::class)],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in(self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }
}
