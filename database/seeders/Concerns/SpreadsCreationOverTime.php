<?php

namespace Database\Seeders\Concerns;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Lets a seeder create its records as if they had been entered over the past
 * month, so the dashboard charts, the weekly counts and the "recently added"
 * lists have something to show. While the callback runs, `now()` is the given
 * moment, so `created_at`, `updated_at` and the rights history `recorded_at`
 * all agree.
 */
trait SpreadsCreationOverTime
{
    /**
     * The number of days the seeded records cover, today included. The dashboard
     * activity chart shows the same 30 days.
     */
    private const SPREAD_WINDOW_DAYS = 30;

    /**
     * Pick `$count` random moments across the last 30 days, in a random order
     * (so the records are created in a different order on every run).
     *
     * When there are at least as many records as days, every day gets at least
     * one record and the rest land on random days. With fewer records, the days
     * are cut into equal parts and each record lands on a random day inside its
     * part, so the whole month is still covered, and the first record always
     * lands on the oldest day. The time of day is random
     * within working hours, and no moment is in the future.
     *
     * @return list<CarbonImmutable>
     */
    private function spreadMoments(int $count): array
    {
        $days = self::SPREAD_WINDOW_DAYS;
        $daysAgo = [];

        if ($count >= $days) {
            $daysAgo = range(0, $days - 1);

            for ($extra = $days; $extra < $count; $extra++) {
                $daysAgo[] = mt_rand(0, $days - 1);
            }
        } else {
            for ($index = 0; $index < $count; $index++) {
                $progress = $index === 0 ? 0.0 : ($index + mt_rand() / mt_getrandmax()) / $count;
                $daysAgo[] = ($days - 1) - (int) floor($progress * $days);
            }
        }

        shuffle($daysAgo);

        return array_map(
            fn (int $daysBack): CarbonImmutable => $this->notAfterNow(
                CarbonImmutable::now()->subDays($daysBack)->setTime(mt_rand(9, 18), mt_rand(0, 59), mt_rand(0, 59)),
            ),
            $daysAgo,
        );
    }

    /**
     * Never go past the real current time.
     */
    private function notAfterNow(CarbonInterface $moment): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return $moment->greaterThan($now) ? $now : $moment->toImmutable();
    }

    /**
     * Run the callback with the clock set to the given moment, then put the
     * clock back.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function atMoment(CarbonInterface $moment, Closure $callback): mixed
    {
        $previous = Carbon::getTestNow();

        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow($previous);
            CarbonImmutable::setTestNow($previous);
        }
    }
}
