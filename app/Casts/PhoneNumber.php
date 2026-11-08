<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A phone number stored as dash-free E.164 text, e.g. `+60110000003`.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class PhoneNumber implements CastsAttributes
{
    private const DEFAULT_COUNTRY_CALLING_CODE = '60';

    /**
     * Remove the separators (spaces, dashes, dots and brackets). A number that
     * starts with 0 is a Malaysian national number, so it gets +60. Anything
     * else is left as written so the validation rule can reject it.
     */
    public static function normalize(mixed $value): mixed
    {
        if ($value !== null && ! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/[\s\-.()]+/', '', (string) $value);

        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '0')) {
            return '+'.self::DEFAULT_COUNTRY_CALLING_CODE.substr($value, 1);
        }

        return $value;
    }

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return self::normalize($value);
    }
}
