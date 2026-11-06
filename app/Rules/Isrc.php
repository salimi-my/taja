<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Isrc implements ValidationRule
{
    /**
     * Strip hyphens and uppercase an ISRC so it can be compared and stored.
     */
    public static function normalize(string $value): string
    {
        return strtoupper(str_replace('-', '', trim($value)));
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[A-Z]{2}[A-Z0-9]{3}\d{7}$/', self::normalize($value))) {
            $fail('The :attribute must be an ISRC of 12 characters, e.g. MYABC2600001.');
        }
    }
}
