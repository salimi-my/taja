<?php

namespace App\Rules;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TerritoryList implements ValidationRule
{
    public const WORLDWIDE = 'WW';

    /**
     * @param  bool  $mustExist  Also require every country code to exist in the `countries` table.
     */
    public function __construct(private bool $mustExist = false) {}

    /**
     * Split a list written as an array or as text separated by `|`, `,` or `;`
     * into trimmed, uppercased, non-empty items.
     *
     * @return list<string>
     */
    public static function parse(mixed $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[|,;]/', (string) $value);

        $items = array_map(
            fn (mixed $item): string => strtoupper(trim((string) $item)),
            $items ?: [],
        );

        return array_values(array_filter($items, fn (string $item): bool => $item !== ''));
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) && ! is_string($value)) {
            $fail('The :attribute must be a list of territories.');

            return;
        }

        $territories = self::parse($value);

        if ($territories === []) {
            $fail('The :attribute must contain at least one territory.');

            return;
        }

        foreach ($territories as $territory) {
            if ($territory !== self::WORLDWIDE && ! preg_match('/^[A-Z]{2}$/', $territory)) {
                $fail('The :attribute must contain only WW or two-letter ISO country codes such as MY, SG or BN.');

                return;
            }
        }

        if (in_array(self::WORLDWIDE, $territories, true) && count($territories) > 1) {
            $fail('The :attribute cannot combine WW with other territories.');

            return;
        }

        if ($this->mustExist) {
            $this->failOnUnknownCountries($territories, $fail);
        }
    }

    /**
     * Fail when a country code is not in the `countries` table.
     *
     * @param  list<string>  $territories
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    private function failOnUnknownCountries(array $territories, Closure $fail): void
    {
        $codes = array_values(array_diff($territories, [self::WORLDWIDE]));

        $unknown = array_diff($codes, Country::codes());

        if ($unknown !== []) {
            $fail('The :attribute contains an unknown country code: '.implode(', ', $unknown).'.');
        }
    }
}
