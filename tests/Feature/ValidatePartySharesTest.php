<?php

use App\Actions\Works\ResolveInitialEffectiveDate;
use App\Actions\Works\ValidatePartyShares;
use App\Enums\PartyRole;
use App\Models\Writer;

/**
 * One party row for the share validator.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function partyRow(array $overrides = []): array
{
    return [
        'writer_id' => 1,
        'publisher_id' => null,
        'role' => PartyRole::C,
        'performance_share' => 50,
        'mechanical_share' => 50,
        'sync_share' => null,
        'lyrics_share' => null,
        'territories_controlled' => ['WW'],
        'effective_start_date' => '2026-01-01',
        'effective_end_date' => null,
        ...$overrides,
    ];
}

function validateParties(array $parties): array
{
    return app(ValidatePartyShares::class)($parties);
}

describe('share sums', function () {
    test('accepts shares that add up to exactly 100', function () {
        expect(validateParties([partyRow(['writer_id' => 1]), partyRow(['writer_id' => 2, 'role' => PartyRole::A])]))->toBe([]);
    });

    test('accepts shares that add up to less than 100', function () {
        expect(validateParties([partyRow(['performance_share' => 30, 'mechanical_share' => 30])]))->toBe([]);
    });

    test('rejects a total above 100 and names the rights type', function (string $column, string $label) {
        $errors = validateParties([
            partyRow([$column => 60]),
            partyRow(['writer_id' => 2, 'role' => PartyRole::A, $column => 60]),
        ]);

        expect($errors)->toHaveCount(1)
            ->and($errors[0]['field'])->toBe('parties')
            ->and($errors[0]['message'])->toContain($label)
            ->and($errors[0]['message'])->toContain('120%');
    })->with([
        'performance' => ['performance_share', 'performance'],
        'mechanical' => ['mechanical_share', 'mechanical'],
        'sync' => ['sync_share', 'sync'],
        'lyrics' => ['lyrics_share', 'lyrics'],
    ]);

    test('allows an empty optional share', function () {
        expect(validateParties([partyRow(['sync_share' => null]), partyRow(['writer_id' => 2, 'role' => PartyRole::A, 'sync_share' => 100])]))->toBe([]);
    });

    test('accepts decimal shares that add up to exactly 100', function () {
        expect(validateParties([
            partyRow(['performance_share' => '33.34', 'mechanical_share' => 10]),
            partyRow(['writer_id' => 2, 'role' => PartyRole::A, 'performance_share' => '33.33', 'mechanical_share' => 10]),
            partyRow(['writer_id' => 3, 'role' => PartyRole::AR, 'performance_share' => '33.33', 'mechanical_share' => 10]),
        ]))->toBe([]);
    });

    test('only counts rows that are in effect at the same time', function () {
        $errors = validateParties([
            partyRow(['performance_share' => 100, 'effective_end_date' => '2026-06-30']),
            partyRow(['writer_id' => 2, 'role' => PartyRole::A, 'performance_share' => 100, 'effective_start_date' => '2026-07-01']),
        ]);

        expect($errors)->toBe([]);
    });

    test('counts rows that overlap by a single day', function () {
        $errors = validateParties([
            partyRow(['performance_share' => 100, 'effective_end_date' => '2026-07-01']),
            partyRow(['writer_id' => 2, 'role' => PartyRole::A, 'performance_share' => 100, 'effective_start_date' => '2026-07-01']),
        ]);

        expect($errors)->toHaveCount(1);
    });
});

describe('duplicate parties', function () {
    test('rejects the same writer with the same role and overlapping territories', function () {
        $errors = validateParties([
            partyRow(['performance_share' => 20, 'mechanical_share' => 20]),
            partyRow(['performance_share' => 20, 'mechanical_share' => 20]),
        ]);

        expect($errors)->toHaveCount(1)
            ->and($errors[0]['field'])->toBe('parties.1.writer_id');
    });

    test('reports a duplicate publisher on the publisher field', function () {
        $errors = validateParties([
            partyRow(['writer_id' => null, 'publisher_id' => 5, 'role' => PartyRole::E, 'performance_share' => 10, 'mechanical_share' => 10]),
            partyRow(['writer_id' => null, 'publisher_id' => 5, 'role' => PartyRole::E, 'performance_share' => 10, 'mechanical_share' => 10]),
        ]);

        expect($errors[0]['field'])->toBe('parties.1.publisher_id');
    });

    test('allows the same writer with a different role', function () {
        expect(validateParties([
            partyRow(['role' => PartyRole::C, 'performance_share' => 20, 'mechanical_share' => 20]),
            partyRow(['role' => PartyRole::A, 'performance_share' => 20, 'mechanical_share' => 20]),
        ]))->toBe([]);
    });

    test('a writer and a publisher with the same id are different parties', function () {
        expect(validateParties([
            partyRow(['writer_id' => 1, 'performance_share' => 20, 'mechanical_share' => 20]),
            partyRow(['writer_id' => null, 'publisher_id' => 1, 'role' => PartyRole::E, 'performance_share' => 20, 'mechanical_share' => 20]),
        ]))->toBe([]);
    });

    test('allows the same party in different territories', function () {
        expect(validateParties([
            partyRow(['territories_controlled' => ['MY'], 'performance_share' => 20, 'mechanical_share' => 20]),
            partyRow(['territories_controlled' => ['SG'], 'performance_share' => 20, 'mechanical_share' => 20]),
        ]))->toBe([]);
    });

    test('worldwide overlaps every territory', function () {
        $errors = validateParties([
            partyRow(['territories_controlled' => ['WW'], 'performance_share' => 20, 'mechanical_share' => 20]),
            partyRow(['territories_controlled' => ['SG'], 'performance_share' => 20, 'mechanical_share' => 20]),
        ]);

        expect($errors)->toHaveCount(1);
    });

    test('allows the same party in periods that do not overlap', function () {
        expect(validateParties([
            partyRow(['effective_end_date' => '2026-06-30']),
            partyRow(['effective_start_date' => '2026-07-01']),
        ]))->toBe([]);
    });
});

describe('initial effective date', function () {
    test('prefers the administration start date', function () {
        $writer = Writer::factory()->make(['agreement_start_date' => '2024-01-01']);

        expect(app(ResolveInitialEffectiveDate::class)('2025-05-05', $writer)->toDateString())->toBe('2025-05-05');
    });

    test('falls back to the agreement start date of the party', function () {
        $writer = Writer::factory()->make(['agreement_start_date' => '2024-01-01']);

        expect(app(ResolveInitialEffectiveDate::class)(null, $writer)->toDateString())->toBe('2024-01-01');
    });

    test('falls back to today', function () {
        expect(app(ResolveInitialEffectiveDate::class)(null, null)->toDateString())->toBe(now()->toDateString());
    });
});
