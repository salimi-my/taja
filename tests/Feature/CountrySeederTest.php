<?php

use App\Models\Country;
use Database\Seeders\CountrySeeder;

test('seeds every ISO 3166-1 country and territory', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(249)
        ->and(Country::where('code', 'MY')->value('name'))->toBe('Malaysia')
        ->and(Country::where('code', 'SG')->value('name'))->toBe('Singapore');
});

test('every code is a unique uppercase two-letter code and worldwide is not a country', function () {
    $this->seed(CountrySeeder::class);

    $codes = Country::pluck('code');

    expect($codes->unique())->toHaveCount(249)
        ->and($codes->every(fn (string $code): bool => preg_match('/^[A-Z]{2}$/', $code) === 1))->toBeTrue()
        ->and($codes)->not->toContain('WW');
});

test('running the seeder again does not duplicate or lose countries', function () {
    Country::factory()->create(['code' => 'MY', 'name' => 'Old Name']);

    $this->seed(CountrySeeder::class);
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(249)
        ->and(Country::where('code', 'MY')->value('name'))->toBe('Malaysia');
});
