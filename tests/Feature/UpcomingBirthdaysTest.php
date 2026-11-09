<?php

use App\Models\User;
use App\Models\Writer;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 9)->startOfDay());
});

/**
 * Create a writer whose birthday falls the given days from today, turning the given age.
 */
function writerWithBirthday(int $daysAhead, int $turning = 40, array $attributes = []): Writer
{
    return Writer::factory()->create([
        'date_of_birth' => now()->addDays($daysAhead)->subYears($turning)->toDateString(),
        ...$attributes,
    ]);
}

test('the dashboard lists the birthdays from today up to three days ahead, soonest first', function () {
    $later = writerWithBirthday(3, 30, ['name' => 'Zed Writer']);
    $today = writerWithBirthday(0, 45, ['name' => 'Today Writer']);
    $tomorrowB = writerWithBirthday(1, 28, ['name' => 'Bea Writer']);
    $tomorrowA = writerWithBirthday(1, 52, ['name' => 'Abe Writer']);
    writerWithBirthday(4);
    writerWithBirthday(-1);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingBirthdays', 4)
            ->where('upcomingBirthdays.0', [
                'id' => $today->id,
                'name' => 'Today Writer',
                'writer_code' => $today->writer_code,
                'date' => '2026-11-09',
                'days_until' => 0,
                'turning_age' => 45,
            ])
            ->where('upcomingBirthdays.1.id', $tomorrowA->id)
            ->where('upcomingBirthdays.2.id', $tomorrowB->id)
            ->where('upcomingBirthdays.3.id', $later->id)
            ->where('upcomingBirthdays.3.days_until', 3)
            ->where('upcomingBirthdays.3.turning_age', 30)
        );
});

test('every role sees the birthdays', function () {
    writerWithBirthday(0);

    foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('upcomingBirthdays', 1));
    }
});

test('writers without a birthday, who died or were deleted are left out', function () {
    writerWithBirthday(0, 40, ['date_of_death' => now()->subYear()->toDateString()]);
    writerWithBirthday(0)->delete();
    Writer::factory()->create(['date_of_birth' => null]);
    $alive = writerWithBirthday(0);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingBirthdays', 1)
            ->where('upcomingBirthdays.0.id', $alive->id)
        );
});

test('the reminder looks across the end of the year', function () {
    $this->travelTo(now()->setDate(2026, 12, 30)->startOfDay());

    $newYearsDay = Writer::factory()->create(['date_of_birth' => '1990-01-01']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingBirthdays', 1)
            ->where('upcomingBirthdays.0.id', $newYearsDay->id)
            ->where('upcomingBirthdays.0.date', '2027-01-01')
            ->where('upcomingBirthdays.0.turning_age', 37)
        );
});

test('a leap day birthday is celebrated on 28 February when the year has no 29 February', function () {
    $this->travelTo(now()->setDate(2027, 2, 27)->startOfDay());

    $leapDayBaby = Writer::factory()->create(['date_of_birth' => '2000-02-29']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingBirthdays', 1)
            ->where('upcomingBirthdays.0.id', $leapDayBaby->id)
            ->where('upcomingBirthdays.0.date', '2027-02-28')
            ->where('upcomingBirthdays.0.turning_age', 27)
        );
});

test('a leap day birthday is celebrated on 29 February in a leap year', function () {
    $this->travelTo(now()->setDate(2028, 2, 27)->startOfDay());

    Writer::factory()->create(['date_of_birth' => '2000-02-29']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcomingBirthdays', 1)
            ->where('upcomingBirthdays.0.date', '2028-02-29')
            ->where('upcomingBirthdays.0.days_until', 2)
        );
});
