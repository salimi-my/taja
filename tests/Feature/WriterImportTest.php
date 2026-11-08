<?php

use App\Enums\CsvImportType;
use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Writer;
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
function importWriters(mixed $test, array $rows): CsvImport
{
    uploadCsv($test, CsvImportType::Writers, $rows);

    $csvImport = CsvImport::query()->latest('id')->firstOrFail();

    $test->post(route('imports.confirm', $csvImport));

    return $csvImport->refresh();
}

test('a valid row creates a writer with its pseudonyms', function () {
    importWriters($this, [importWriterRow(1, [
        'Writer_Role' => 'composer/author',
        'Status' => 'active',
        'Rights_Types_Controlled' => 'Performance|Sync',
        'Controlled_Territories' => 'WW',
        'Pseudonym_Stage_Name_1' => 'Stage One',
        'IPI_Base_Number_1' => 'I-000000003-3',
        'Pseudonym_Stage_Name_2' => '',
        'IPI_Base_Number_2' => '',
        'Pseudonym_Stage_Name_3' => 'Stage Three',
        'IPI_Base_Number_3' => '',
    ])]);

    $writer = Writer::query()->with('aliases')->firstOrFail();

    expect($writer->writer_code)->toBe('WRT-'.str_pad((string) $writer->id, 6, '0', STR_PAD_LEFT))
        ->and($writer->name)->toBe('Fake Writer 1')
        ->and($writer->default_role)->toBe(PartyRole::CA)
        ->and($writer->status)->toBe(MasterStatus::Active)
        ->and($writer->controlled_territories)->toBe(['WW'])
        ->and($writer->rights_types_controlled->pluck('value')->all())->toBe(['performance', 'sync'])
        ->and($writer->aliases->map(fn ($alias) => [$alias->position, $alias->name, $alias->ipi_base_number])->all())
        ->toBe([[1, 'Stage One', 'I-000000003-3'], [2, 'Stage Three', null]]);
});

test('a writer can be linked to a publisher by its code', function () {
    $publisher = Publisher::factory()->create();

    importWriters($this, [importWriterRow(1, ['Publisher_Client_ID' => strtolower($publisher->publisher_code)])]);

    expect(Writer::query()->firstOrFail()->publisher_id)->toBe($publisher->id);
});

test('an unknown publisher code is rejected', function () {
    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, ['Publisher_Client_ID' => 'PUB-999999'])]);

    $issues = latestImportIssues();

    expect($issues)->toHaveKey('2:Publisher_Client_ID')
        ->and($issues['2:Publisher_Client_ID'][0])->toContain('PUB-999999');
});

test('a soft-deleted publisher cannot be linked', function () {
    $publisher = Publisher::factory()->create();
    $publisher->delete();

    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, ['Publisher_Client_ID' => $publisher->publisher_code])]);

    expect(latestImportIssues())->toHaveKey('2:Publisher_Client_ID');
});

test('a role that is not a writer role is rejected', function (string $role) {
    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, ['Writer_Role' => $role])]);

    expect(latestImportIssues())->toHaveKey('2:Writer_Role');
})->with(['E', 'SE', 'Singer']);

test('a repeated IPI number in the file or the database is rejected', function () {
    $existing = Writer::factory()->create();

    uploadCsv($this, CsvImportType::Writers, [
        importWriterRow(1),
        importWriterRow(2, ['IPI_Name_Number' => importWriterRow(1)['IPI_Name_Number']]),
        importWriterRow(3, ['IPI_Name_Number' => $existing->ipi_name_number]),
    ]);

    $issues = latestImportIssues();

    expect($issues)->toHaveKeys(['3:IPI_Name_Number', '4:IPI_Name_Number'])
        ->and($issues['3:IPI_Name_Number'][0])->toContain('already used on line 2')
        ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(1);
});

test('NRIC and phone numbers are saved without dashes', function () {
    importWriters($this, [importWriterRow(1, [
        'NRIC' => '900101-01-1234',
        'Heir_NRIC' => 'A-123-456',
        'Phone' => '+6011-000 0003',
        'Heir_Mobile_Number' => '011-000 0004',
    ])]);

    $writer = Writer::query()->firstOrFail();

    expect($writer->getRawOriginal('nric'))->toBe('900101011234')
        ->and($writer->getRawOriginal('heir_nric'))->toBe('A123456')
        ->and($writer->getRawOriginal('phone'))->toBe('+60110000003')
        ->and($writer->getRawOriginal('heir_mobile_number'))->toBe('+60110000004');
});

test('a phone number that is not international is reported against its column', function () {
    uploadCsv($this, CsvImportType::Writers, [
        importWriterRow(1),
        importWriterRow(2, ['Phone' => 'call me']),
    ]);

    expect(latestImportIssues())->toHaveKey('3:Phone')
        ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(1);
});

test('an NRIC repeated with and without dashes is still a warning', function () {
    Writer::factory()->create(['nric' => '900101011234']);

    importWriters($this, [
        importWriterRow(1, ['NRIC' => '900101-01-1234']),
    ]);

    expect(latestImportIssues('warnings'))->toHaveKey('2:NRIC');
});

test('a repeated NRIC is only a warning and the writers are still imported', function () {
    $existing = Writer::factory()->create(['nric' => 'SAME-NRIC']);

    importWriters($this, [
        importWriterRow(1, ['NRIC' => 'SAME-NRIC']),
        importWriterRow(2, ['NRIC' => 'OTHER-NRIC']),
        importWriterRow(3, ['NRIC' => 'OTHER-NRIC']),
    ]);

    $warnings = latestImportIssues('warnings');

    expect($warnings)->toHaveKeys(['2:NRIC', '4:NRIC'])
        ->and($warnings['2:NRIC'][0])->toContain('already exists')
        ->and($warnings['4:NRIC'][0])->toContain('line 3')
        ->and(CsvImport::query()->firstOrFail()->error_rows)->toBe(0)
        ->and(Writer::query()->count())->toBe(4)
        ->and($existing->exists)->toBeTrue();
});

test('a pseudonym IPI number needs a pseudonym', function () {
    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, [
        'Pseudonym_Stage_Name_1' => '',
        'IPI_Base_Number_1' => 'I-000000004-4',
    ])]);

    expect(latestImportIssues())->toHaveKey('2:Pseudonym_Stage_Name_1');
});

test('deals that do not total 100 are rejected', function () {
    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, [
        'Taja_Percentage_Deal' => '70',
        'Writer_Percentage_Deal' => '40',
    ])]);

    expect(latestImportIssues())->toHaveKey('2:Writer_Percentage_Deal');
});

test('required columns and bad values are reported against their column', function (string $header, string $value) {
    uploadCsv($this, CsvImportType::Writers, [importWriterRow(1, [$header => $value])]);

    expect(latestImportIssues())->toHaveKey("2:{$header}");
})->with([
    'missing name' => ['Name', ''],
    'missing society' => ['CMO_Society', ''],
    'bad status' => ['Status', 'Dormant'],
    'bad start date' => ['Agreement_Start_Date', '2026/01/01'],
    'bad birth date' => ['Date_Of_Birth', '20-05-1990'],
    'bad email' => ['Email', 'nope'],
    'bad territory' => ['Controlled_Territories', 'MY|ZZ'],
]);

test('an invalid row never creates a writer or pseudonym', function () {
    importWriters($this, [importWriterRow(1, ['Status' => 'Dormant', 'Pseudonym_Stage_Name_1' => 'Stage'])]);

    expect(Writer::query()->count())->toBe(0);
});

test('repeated NRICs and publisher codes are found across chunks', function () {
    config(['imports.chunk_size' => 2]);

    $publisher = Publisher::factory()->create();

    importWriters($this, [
        importWriterRow(1, ['NRIC' => 'SAME']),
        importWriterRow(2, ['Publisher_Client_ID' => $publisher->publisher_code]),
        importWriterRow(3),
        importWriterRow(4, ['NRIC' => 'SAME', 'Publisher_Client_ID' => 'PUB-999999']),
        importWriterRow(5, ['Publisher_Client_ID' => $publisher->publisher_code]),
    ]);

    expect(latestImportIssues('warnings'))->toHaveKey('5:NRIC')
        ->and(latestImportIssues())->toHaveKey('5:Publisher_Client_ID')
        ->and(Writer::query()->where('publisher_id', $publisher->id)->count())->toBe(2)
        ->and(Writer::query()->count())->toBe(4);
});
