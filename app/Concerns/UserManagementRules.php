<?php

namespace App\Concerns;

use App\Casts\PhoneNumber;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Rules\InternationalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait UserManagementRules
{
    /**
     * Get the validation rules for the contact details a user and an admin can both edit.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function contactRules(): array
    {
        return [
            'phone' => ['nullable', 'string', new InternationalPhone],
            'gender' => ['nullable', Rule::enum(Gender::class)],
        ];
    }

    /**
     * Get the validation rules for the fields only an admin manages besides the profile rules.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function managedUserRules(): array
    {
        return [
            ...$this->contactRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Store phone numbers as dash-free E.164 text.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
        }
    }
}
