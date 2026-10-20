<?php

namespace App\Http\Requests\Users;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            ...$this->profileRules($user->id),
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
        ];
    }

    /**
     * Get the validator callbacks that run after the base rules.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                /** @var User $user */
                $user = $this->route('user');

                if ($validator->errors()->has('role')) {
                    return;
                }

                if ($user->is($this->user()) && $this->enum('role', UserRole::class) !== $user->role) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));
                }
            },
        ];
    }
}
