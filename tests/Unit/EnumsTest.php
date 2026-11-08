<?php

use App\Enums\ConflictStatus;
use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Enums\RightsChangeType;
use App\Enums\RightsType;

test('options lists every case as a value and label pair', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()))
        ->and($options[0])->toBe([
            'value' => $enum::cases()[0]->value,
            'label' => $enum::cases()[0]->label(),
        ]);
})->with([
    MasterStatus::class,
    RightsType::class,
    RegistrationStatus::class,
    ConflictStatus::class,
    RightsChangeType::class,
]);

test('party role options are labelled with the role name and code', function () {
    expect(PartyRole::options())->toHaveCount(count(PartyRole::cases()))
        ->and(PartyRole::options()[0])->toBe(['value' => 'C', 'label' => 'Composer (C)'])
        ->and(PartyRole::SE->displayLabel())->toBe('Sub-Publisher (SE)')
        ->and(PartyRole::E->displayLabel())->toBe('Original Publisher (E)')
        ->and(PartyRole::CA->displayLabel())->toBe('Composer/Author (CA)')
        ->and(PartyRole::SA->displayLabel())->toBe('Sub-Author (SA)');
});

test('party roles are split into writer and publisher roles', function (PartyRole $role, bool $isWriter, bool $isPublisher) {
    expect($role->isWriterRole())->toBe($isWriter)
        ->and($role->isPublisherRole())->toBe($isPublisher);
})->with([
    'composer' => [PartyRole::C, true, false],
    'author' => [PartyRole::A, true, false],
    'composer author' => [PartyRole::CA, true, false],
    'arranger' => [PartyRole::AR, true, false],
    'sub author' => [PartyRole::SA, true, false],
    'original publisher' => [PartyRole::E, false, true],
    'sub publisher' => [PartyRole::SE, false, true],
]);

test('fromImport finds a case from loosely written text', function (string $text) {
    expect(ConflictStatus::fromImport($text))->toBe(ConflictStatus::NoConflict);
})->with([
    'label' => 'No Conflict',
    'value' => 'no_conflict',
    'case name' => 'NoConflict',
    'mixed case with padding' => '  NO conflict ',
    'hyphenated' => 'no-conflict',
]);

test('fromImport finds role codes and returns null for unknown or blank text', function () {
    expect(PartyRole::fromImport('ca'))->toBe(PartyRole::CA)
        ->and(PartyRole::fromImport('Sub-Publisher'))->toBe(PartyRole::SE)
        ->and(PartyRole::fromImport('Sub-Author'))->toBe(PartyRole::SA)
        ->and(PartyRole::fromImport('X'))->toBeNull()
        ->and(PartyRole::fromImport('  '))->toBeNull();
});
