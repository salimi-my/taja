<?php

namespace App\Actions\Writers;

use App\Models\Writer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The writers who have a birthday today or in the next few days, for the
 * dashboard reminder. Writers without a date of birth, writers who have died
 * and soft-deleted writers are left out.
 *
 * A birthday on 29 February is celebrated on 28 February in a year that is
 * not a leap year.
 */
class UpcomingBirthdays
{
    /**
     * How many days ahead of a birthday the reminder starts.
     */
    public const LOOKAHEAD_DAYS = 3;

    /**
     * List the birthdays from today up to the lookahead, soonest first and
     * by name within a day. The age is the one the writer turns.
     *
     * @return list<array{id: int, name: string, writer_code: string|null, date: string, days_until: int, turning_age: int}>
     */
    public function get(): array
    {
        $today = CarbonImmutable::today();

        $days = array_map(
            fn (int $offset): CarbonImmutable => $today->addDays($offset),
            range(0, self::LOOKAHEAD_DAYS),
        );

        $writers = Writer::query()
            ->whereNotNull('date_of_birth')
            ->whereNull('date_of_death')
            ->where(function (Builder $query) use ($days): void {
                foreach ($days as $day) {
                    $query->orWhere(fn (Builder $birthday) => $birthday
                        ->whereMonth('date_of_birth', $day->month)
                        ->whereDay('date_of_birth', $day->day));

                    if ($this->isLeapDayObservance($day)) {
                        $query->orWhere(fn (Builder $birthday) => $birthday
                            ->whereMonth('date_of_birth', 2)
                            ->whereDay('date_of_birth', 29));
                    }
                }
            })
            ->get(['id', 'name', 'writer_code', 'date_of_birth']);

        $birthdays = [];

        foreach ($writers as $writer) {
            foreach ($days as $offset => $day) {
                if (! $this->celebratesOn($writer->date_of_birth, $day)) {
                    continue;
                }

                $birthdays[] = [
                    'id' => $writer->id,
                    'name' => $writer->name,
                    'writer_code' => $writer->writer_code,
                    'date' => $day->toDateString(),
                    'days_until' => $offset,
                    'turning_age' => $day->year - $writer->date_of_birth->year,
                ];

                break;
            }
        }

        usort($birthdays, fn (array $a, array $b): int => [$a['days_until'], $a['name']] <=> [$b['days_until'], $b['name']]);

        return $birthdays;
    }

    /**
     * Whether the date of birth falls on the given day, counting 28 February
     * as the birthday of a leap day baby in a year that is not a leap year.
     */
    private function celebratesOn(CarbonInterface $birth, CarbonImmutable $day): bool
    {
        if ($birth->month === $day->month && $birth->day === $day->day) {
            return true;
        }

        return $this->isLeapDayObservance($day) && $birth->month === 2 && $birth->day === 29;
    }

    /**
     * Whether the day is 28 February of a year without a 29 February.
     */
    private function isLeapDayObservance(CarbonImmutable $day): bool
    {
        return $day->month === 2 && $day->day === 28 && ! $day->isLeapYear();
    }
}
