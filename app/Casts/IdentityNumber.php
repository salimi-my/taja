<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * An NRIC or passport number stored without dashes, e.g. `900101011234`.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class IdentityNumber implements CastsAttributes
{
    /**
     * Remove the dashes. An empty value becomes null.
     */
    public static function normalize(mixed $value): mixed
    {
        if ($value !== null && ! is_string($value)) {
            return $value;
        }

        $value = str_replace('-', '', (string) $value);

        return $value === '' ? null : $value;
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
