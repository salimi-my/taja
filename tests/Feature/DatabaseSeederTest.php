<?php

use App\Actions\Reports\IncompleteSplitReport;
use App\Actions\Writers\UpcomingBirthdays;
use App\Enums\Gender;
use App\Enums\MasterChangeType;
use App\Enums\MasterStatus;
use App\Enums\RightsChangeType;
use App\Enums\UserChangeType;
use App\Models\MasterHistory;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\UserHistory;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

test('the seeders build a catalogue with codes, history and incomplete works', function () {
    $this->seed();

    expect(Publisher::where('name', 'Taja Archive')->exists())->toBeTrue()
        ->and(Publisher::count())->toBe(15)
        ->and(Writer::count())->toBe(25)
        ->and(Work::count())->toBe(60)
        ->and(Work::whereNull('tap_number')->count())->toBe(0)
        ->and(Writer::whereNull('writer_code')->count())->toBe(0)
        ->and(Publisher::whereNull('publisher_code')->count())->toBe(0)
        ->and(Work::has('isrcs')->count())->toBe(60);

    $types = RightsHistory::query()->pluck('change_type')->unique()->all();

    expect($types)->toContain(RightsChangeType::WorkCreated, RightsChangeType::PartyAdded, RightsChangeType::TerritoryChanged, RightsChangeType::Correction, RightsChangeType::PartyRemoved)
        ->and(WorkParty::whereNotNull('superseded_at')->exists())->toBeTrue()
        ->and(WorkParty::whereNotNull('effective_end_date')->exists())->toBeTrue();

    expect(app(IncompleteSplitReport::class)->count())->toBeGreaterThanOrEqual(3)
        ->and(app(IncompleteSplitReport::class)->count())->toBeLessThan(10);
});

test('every seeded writer and publisher has a created entry, and a few have changes', function () {
    $this->seed();

    $created = MasterHistory::query()->where('change_type', MasterChangeType::Created);

    expect($created->count())->toBe(Writer::count() + Publisher::count())
        ->and(MasterHistory::query()->where('change_type', MasterChangeType::Updated)->count())->toBeGreaterThanOrEqual(3)
        ->and(MasterHistory::query()->where('subject_type', 'writer')->whereJsonContainsKey('new_snapshot->bank_name')->exists())->toBeTrue();
});

test('every seeded user has a created entry, and a few have changes', function () {
    $this->seed();

    expect(UserHistory::query()->where('change_type', UserChangeType::Created)->count())->toBe(User::count())
        ->and(UserHistory::query()->where('change_type', UserChangeType::RoleChanged)->exists())->toBeTrue()
        ->and(UserHistory::query()->where('change_type', UserChangeType::Updated)->count())->toBeGreaterThanOrEqual(2)
        ->and(UserHistory::query()->where('change_type', UserChangeType::PasswordChanged)->exists())->toBeTrue();
});

test('the seeded users have a gender and a Malaysian mobile number, with a few gaps', function () {
    $this->seed();

    $john = User::where('email', 'johndoe@example.com')->firstOrFail();
    $jane = User::where('email', 'janedoe@example.com')->firstOrFail();

    expect($john->gender)->toBe(Gender::Male)
        ->and($john->phone)->toStartWith('+60')
        ->and($jane->gender)->toBe(Gender::Female)
        ->and($jane->phone)->toStartWith('+60')
        ->and(User::count())->toBe(50)
        ->and(User::whereNotNull('phone')->where('phone', 'not like', '+60%')->count())->toBe(0)
        ->and(User::whereNull('phone')->count())->toBeGreaterThan(0)
        ->and(User::whereNull('gender')->count())->toBeGreaterThan(0)
        ->and(User::where('gender', Gender::Female->value)->count())->toBeGreaterThan(10)
        ->and(User::where('gender', Gender::Male->value)->count())->toBeGreaterThan(10);
});

test('the seeded masters have believable details that agree with each other', function () {
    $this->seed();

    $writer = Writer::where('name', 'Hafiz Zainuddin bin Samad')->firstOrFail();

    expect($writer->nric)->toStartWith($writer->date_of_birth->format('ymd'))
        ->and($writer->aliases)->toHaveCount(1)
        ->and($writer->taja_percentage_deal + $writer->writer_percentage_deal)->toEqual(100)
        ->and(Writer::query()->whereMonth('date_of_birth', now()->month)->whereDay('date_of_birth', now()->day)->count())->toBe(2)
        ->and(app(UpcomingBirthdays::class)->get())->toHaveCount(6)
        ->and(Writer::whereNotNull('date_of_death')->count())->toBe(1)
        ->and(Writer::where('status', MasterStatus::Inactive)->count())->toBe(1)
        ->and(Writer::whereNotNull('publisher_id')->count())->toBeGreaterThan(0);

    Publisher::query()->each(function (Publisher $publisher): void {
        expect($publisher->official_email)->toEndWith('.example')
            ->and($publisher->person_in_charge_email)->toEndWith(substr($publisher->official_email, 5))
            ->and((float) $publisher->taja_percentage_deal + (float) $publisher->client_percentage_deal)->toEqual(100.0);
    });
});

test('every one of the last 30 days has at least one work and one user created', function () {
    $this->seed();

    $now = CarbonImmutable::now();

    foreach ([Work::class, User::class] as $model) {
        $days = $model::query()->pluck('created_at')->map(fn ($moment) => $moment->toDateString())->unique();

        foreach (range(0, 29) as $daysAgo) {
            expect($days->contains($now->subDays($daysAgo)->toDateString()))->toBeTrue("No {$model} was created {$daysAgo} days ago.");
        }
    }

    $changeDays = RightsHistory::query()->pluck('recorded_at')->map(fn ($moment) => $moment->toDateString())->unique();

    foreach (range(0, 29) as $daysAgo) {
        expect($changeDays->contains($now->subDays($daysAgo)->toDateString()))->toBeTrue("No rights change was recorded {$daysAgo} days ago.");
    }
});

test('the seeded records are created at random moments over the past month', function () {
    $this->seed();

    $now = CarbonImmutable::now();
    $distinctDays = fn (string $model, string $column = 'created_at'): int => $model::query()->pluck($column)->map(fn ($moment) => $moment->toDateString())->unique()->count();

    expect(User::query()->orderBy('created_at')->first()->created_at->toDateString())->toBe($now->subDays(29)->toDateString())
        ->and(User::query()->orderByDesc('created_at')->first()->created_at->greaterThan($now->subDays(2)))->toBeTrue()
        ->and($distinctDays(User::class))->toBeGreaterThan(15)
        ->and($distinctDays(Writer::class))->toBeGreaterThan(8)
        ->and($distinctDays(Publisher::class))->toBeGreaterThan(5)
        ->and($distinctDays(Work::class))->toBe(30)
        ->and(Work::query()->orderBy('created_at')->first()->created_at->lessThanOrEqualTo($now->subDays(28)))->toBeTrue()
        ->and(Work::query()->orderByDesc('created_at')->first()->created_at->greaterThan($now->subDay()))->toBeTrue();

    foreach ([User::class, Publisher::class, Writer::class, Work::class] as $model) {
        expect($model::query()->where('created_at', '>', $now)->count())->toBe(0)
            ->and($model::query()->where('created_at', '<', $now->subDays(31))->count())->toBe(0);
    }

    Work::query()->with('parties.writer', 'parties.publisher')->each(function (Work $work): void {
        foreach ($work->parties as $party) {
            $master = $party->writer ?? $party->publisher;

            expect($master->created_at->lessThan($work->created_at))->toBeTrue();
        }
    });

    RightsHistory::query()->with('work')->each(function (RightsHistory $entry) use ($now): void {
        expect($entry->recorded_at->greaterThanOrEqualTo($entry->work->created_at))->toBeTrue()
            ->and($entry->recorded_at->greaterThan($now->addMinute()))->toBeFalse();
    });
});

test('the seeders put the clock back after they run', function () {
    $this->seed();

    expect(Carbon::hasTestNow())->toBeFalse();
});

test('every seeded work has real titles and starts after the agreements of its parties', function () {
    $this->seed();

    Work::query()->with('parties')->each(function (Work $work): void {
        expect($work->english_title)->not->toBeEmpty()
            ->and($work->artist_name)->not->toBeEmpty()
            ->and($work->duration)->toMatch('/^00:0[1-6]:\d{2}$/');

        foreach ($work->parties as $party) {
            $master = $party->writer ?? $party->publisher;

            expect(CarbonImmutable::parse($party->effective_start_date)->gte($master->agreement_start_date))->toBeTrue();
        }
    });

    expect(Work::where('is_public_domain', true)->count())->toBe(1)
        ->and(Work::whereNotNull('other_title')->count())->toBeGreaterThan(10);
});
