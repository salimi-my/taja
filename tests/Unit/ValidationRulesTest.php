<?php

use App\Rules\DurationHms;
use App\Rules\IpiNameNumber;
use App\Rules\Isrc;
use App\Rules\Iswc;
use App\Rules\Percent;
use App\Rules\TerritoryList;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Run a rule against a value and report whether it passed.
 */
function passes(ValidationRule $rule, mixed $value): bool
{
    $failed = false;

    $rule->validate('field', $value, function () use (&$failed): void {
        $failed = true;
    });

    return ! $failed;
}

test('territory list accepts WW or ISO codes in any supported format', function (mixed $value) {
    expect(passes(new TerritoryList, $value))->toBeTrue();
})->with([
    'worldwide' => 'WW',
    'pipe separated' => 'MY|SG|BN',
    'comma separated' => 'my, sg',
    'semicolon separated' => 'MY;SG',
    'array' => [['MY', 'sg']],
]);

test('territory list rejects bad territories', function (mixed $value) {
    expect(passes(new TerritoryList, $value))->toBeFalse();
})->with([
    'empty' => '',
    'three letters' => 'MYS',
    'digits' => 'M1',
    'worldwide mixed with a country' => 'WW|MY',
    'not text' => 12,
]);

test('territory list parse normalizes the separators and case', function () {
    expect(TerritoryList::parse(' my | SG ,bn;; '))->toBe(['MY', 'SG', 'BN'])
        ->and(TerritoryList::parse(['my', ' ']))->toBe(['MY']);
});

test('percent accepts numbers from 0 to 100 with at most 2 decimals', function (mixed $value) {
    expect(passes(new Percent, $value))->toBeTrue();
})->with([0, 100, 33.33, '50', '12.5', '100.00']);

test('percent rejects other values', function (mixed $value) {
    expect(passes(new Percent, $value))->toBeFalse();
})->with([
    'over 100' => 100.01,
    'negative' => -1,
    'three decimals' => '12.345',
    'percent sign' => '50%',
    'text' => 'abc',
    'boolean' => true,
]);

test('duration accepts HH:MM:SS only', function (string $value, bool $valid) {
    expect(passes(new DurationHms, $value))->toBe($valid);
})->with([
    ['00:03:45', true],
    ['01:59:59', true],
    ['00:60:00', false],
    ['00:00:60', false],
    ['3:45', false],
    ['00:3:45', false],
]);

test('iswc is T followed by 10 digits', function (string $value, bool $valid) {
    expect(passes(new Iswc, $value))->toBe($valid);
})->with([
    ['T1234567890', true],
    ['t1234567890', false],
    ['T123456789', false],
    ['T12345678901', false],
    ['1234567890', false],
]);

test('isrc ignores hyphens and case and needs 12 valid characters', function (string $value, bool $valid) {
    expect(passes(new Isrc, $value))->toBe($valid);
})->with([
    ['MYABC2600001', true],
    ['my-abc-26-00001', true],
    ['MYABC260000', false],
    ['1YABC2600001', false],
    ['MYABC26X0001', false],
]);

test('isrc normalize strips hyphens and uppercases', function () {
    expect(Isrc::normalize(' my-abc-26-00001 '))->toBe('MYABC2600001');
});

test('ipi name number is exactly 11 digits', function (string $value, bool $valid) {
    expect(passes(new IpiNameNumber, $value))->toBe($valid);
})->with([
    ['00123456789', true],
    ['1234567890', false],
    ['123456789012', false],
    ['1234567890A', false],
]);
