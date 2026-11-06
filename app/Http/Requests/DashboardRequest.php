<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public const ACTIVITY_DAY_OPTIONS = [7, 14, 30, 90];

    public const DEFAULT_ACTIVITY_DAYS = 30;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `days` is the period of the activity chart.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', Rule::in(self::ACTIVITY_DAY_OPTIONS)],
        ];
    }

    /**
     * Get how many days the activity chart covers.
     */
    public function activityDays(): int
    {
        return (int) ($this->validated('days') ?? self::DEFAULT_ACTIVITY_DAYS);
    }
}
