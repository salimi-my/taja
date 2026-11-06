<?php

use App\Enums\CsvImportType;
use App\Enums\MasterStatus;
use App\Enums\RightsType;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CountrySeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * Preview and confirm the given rows.
 *
 * @param  list<array<string, string>>  $rows
 */
function importPublishers(mixed $test, array $rows): CsvImport
{
    uploadCsv($test, CsvImportType::Publishers, $rows);

    $csvImport = CsvImport::query()->latest('id')->firstOrFail();

    $test->post(route('imports.confirm', $csvImport));

    return $csvImport->refresh();
}

test('a valid row creates a publisher with every field', function () {
    importPublishers($this, [importPublisherRow(1, [
        'Rights_Types_Granted' => 'performance|MECHANICAL',
        'Status' => 'ACTIVE',
        'Territories_Granted' => 'my, sg',
        'Auto_Renewal' => 'yes',
        'Pre_Term' => 'N',
        'Taja_Percentage_Deal' => '60',
        'Client_Percentage_Deal' => '40',
    ])]);

    $publisher = Publisher::query()->firstOrFail();

    expect($publisher->publisher_code)->toBe('PUB-'.str_pad((string) $publisher->id, 6, '0', STR_PAD_LEFT))
        ->and($publisher->name)->toBe('Fake Publisher 1')
        ->and($publisher->rights_types_granted->all())->toBe([RightsType::Performance, RightsType::Mechanical])
        ->and($publisher->status)->toBe(MasterStatus::Active)
        ->and($publisher->territories_granted)->toBe(['MY', 'SG'])
        ->and($publisher->auto_renewal)->toBeTrue()
        ->and($publisher->pre_term)->toBeFalse()
        ->and($publisher->agreement_start_date->toDateString())->toBe('2026-01-01')
        ->and($publisher->taja_percentage_deal)->toBe('60.00')
        ->and($publisher->client_percentage_deal)->toBe('40.00');
});

test('optional columns can be left blank', function () {
    importPublishers($this, [importPublisherRow(1, [
        'IPI_Name_Number' => '',
        'Incorporation_Date' => '',
        'Agreement_End_Date' => '',
        'Auto_Renewal' => '',
        'Taja_Percentage_Deal' => '',
        'Client_Percentage_Deal' => '',
    ])]);

    $publisher = Publisher::query()->firstOrFail();

    expect($publisher->ipi_name_number)->toBeNull()
        ->and($publisher->auto_renewal)->toBeNull()
        ->and($publisher->taja_percentage_deal)->toBeNull();
});

test('a bad value is reported against its column and line', function (string $header, string $value, string $expected) {
    uploadCsv($this, CsvImportType::Publishers, [
        importPublisherRow(1),
        importPublisherRow(2, [$header => $value]),
    ]);

    $issues = latestImportIssues();

    expect($issues)->toHaveKey("3:{$header}")
        ->and($issues["3:{$header}"][0])->toContain($expected)
        ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(1);
})->with([
    'date format' => ['Agreement_Start_Date', '01/02/2026', 'Agreement_Start_Date'],
    'percent with a sign' => ['Taja_Percentage_Deal', '60%', 'percent'],
    'percent above 100' => ['Taja_Percentage_Deal', '120', 'percent'],
    'email' => ['Official_Email', 'not-an-email', 'Official_Email'],
    'unknown country' => ['Territories_Granted', 'ZZ', 'unknown country code'],
    'worldwide mixed with a country' => ['Territories_Granted', 'WW|MY', 'WW'],
    'IPI number length' => ['IPI_Name_Number', '123', '11 digits'],
    'unknown rights type' => ['Rights_Types_Granted', 'Performance|Teleport', 'Rights_Types_Granted'],
    'unknown status' => ['Status', 'Dormant', 'Status'],
    'boolean' => ['Auto_Renewal', 'maybe', 'Auto_Renewal'],
    'end date before start date' => ['Agreement_End_Date', '2025-01-01', 'Agreement_End_Date'],
    'missing name' => ['Publisher_Name', '', 'Publisher_Name'],
    'missing territories' => ['Territories_Granted', '', 'Territories_Granted'],
]);

test('deals that do not total 100 are rejected', function () {
    uploadCsv($this, CsvImportType::Publishers, [importPublisherRow(1, [
        'Taja_Percentage_Deal' => '60',
        'Client_Percentage_Deal' => '50',
    ])]);

    expect(latestImportIssues())->toHaveKey('2:Client_Percentage_Deal');
});

test('a single deal percentage is allowed', function () {
    uploadCsv($this, CsvImportType::Publishers, [importPublisherRow(1, [
        'Taja_Percentage_Deal' => '60',
        'Client_Percentage_Deal' => '',
    ])]);

    expect(CsvImport::query()->firstOrFail()->error_rows)->toBe(0);
});

test('a repeated company registration number or IPI number in the file is rejected', function (string $header) {
    uploadCsv($this, CsvImportType::Publishers, [
        importPublisherRow(1),
        importPublisherRow(2, [$header => importPublisherRow(1)[$header]]),
    ]);

    $issues = latestImportIssues();

    expect($issues)->toHaveKey("3:{$header}")
        ->and($issues["3:{$header}"][0])->toContain('already used on line 2')
        ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(1);
})->with(['Company_Registration_Number', 'IPI_Name_Number']);

test('a company registration number or IPI number already in the database is rejected', function (string $header, string $column) {
    $existing = Publisher::factory()->create();

    uploadCsv($this, CsvImportType::Publishers, [
        importPublisherRow(1, [$header => $existing->{$column}]),
    ]);

    expect(latestImportIssues())->toHaveKey("2:{$header}");
})->with([
    'company registration number' => ['Company_Registration_Number', 'company_registration_number'],
    'IPI name number' => ['IPI_Name_Number', 'ipi_name_number'],
]);

test('a soft-deleted publisher keeps its numbers reserved', function () {
    $existing = Publisher::factory()->create();
    $existing->delete();

    uploadCsv($this, CsvImportType::Publishers, [
        importPublisherRow(1, ['Company_Registration_Number' => $existing->company_registration_number]),
    ]);

    expect(latestImportIssues())->toHaveKey('2:Company_Registration_Number');
});

test('an invalid row never creates a publisher', function () {
    importPublishers($this, [importPublisherRow(1, ['Official_Email' => 'nope'])]);

    expect(Publisher::query()->count())->toBe(0);
});
