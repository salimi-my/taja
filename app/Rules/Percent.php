<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Percent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isNumber = is_int($value) || is_float($value) || is_string($value);

        if (
            ! $isNumber
            || ! preg_match('/^\d{1,3}(\.\d{1,2})?$/', (string) $value)
            || (float) $value > 100
        ) {
            $fail('The :attribute must be a percent from 0 to 100 with at most 2 decimals and no % sign.');
        }
    }
}
