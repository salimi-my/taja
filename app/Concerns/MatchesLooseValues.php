<?php

namespace App\Concerns;

/**
 * Lets a backed enum with a `label()` method be found from loosely written
 * text, such as a CSV cell. Matching is case-insensitive and ignores spaces,
 * underscores and hyphens, so `No Conflict`, `no_conflict` and `NoConflict`
 * all find the same case.
 */
trait MatchesLooseValues
{
    /**
     * Find the case that matches the given text, or null when none does.
     */
    public static function fromImport(string $value): ?self
    {
        $needle = self::normalizeLooseValue($value);

        if ($needle === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            $candidates = [$case->name, (string) $case->value, $case->label()];

            foreach ($candidates as $candidate) {
                if (self::normalizeLooseValue($candidate) === $needle) {
                    return $case;
                }
            }
        }

        return null;
    }

    private static function normalizeLooseValue(string $value): string
    {
        return strtolower(preg_replace('/[\s_\-]+/', '', $value) ?? '');
    }
}
