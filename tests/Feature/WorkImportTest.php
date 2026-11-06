<?php

use App\Actions\Imports\ImportRegistry;
use App\Enums\ConflictStatus;
use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Enums\RightsChangeType;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use App\Models\Writer;
use App\Models\WriterAlias;
use Database\Seeders\CountrySeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CountrySeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->writer = Writer::factory()->create([
        'name' => 'Fake Writer',
        'ipi_name_number' => '00000000011',
        'agreement_start_date' => '2025-03-01',
    ]);
    $this->coWriter = Writer::factory()->create([
        'name' => 'Another Fake Writer',
        'ipi_name_number' => '00000000012',
        'agreement_start_date' => '2025-04-01',
    ]);
    $this->publisher = Publisher::factory()->create([
        'name' => 'Fake Publisher',
        'ipi_name_number' => '00000000001',
        'agreement_start_date' => '2025-05-01',
    ]);
});

/**
 * Preview and confirm the given rows.
 *
 * @param  list<array<string, string>>  $rows
 */
function importWorks(mixed $test, array $rows): CsvImport
{
    uploadCsv($test, CsvImportType::Works, $rows);

    $csvImport = CsvImport::query()->latest('id')->firstOrFail();

    $test->post(route('imports.confirm', $csvImport));

    return $csvImport->refresh();
}

/**
 * The three rows of one work: two writers and a publisher, 100% in total.
 *
 * @param  array<string, string>  $workOverrides  Applied to every row.
 * @return list<array<string, string>>
 */
function fakeWorkRows(int $number, array $workOverrides = []): array
{
    return [
        importWorkRow($number, $workOverrides),
        importWorkRow($number, [
            ...$workOverrides,
            'IPI_No' => '00000000012',
            'Contributor_Name' => 'Another Fake Writer',
            'Writer_Roles' => 'A',
        ]),
        importWorkRow($number, [
            ...$workOverrides,
            'IPI_No' => '00000000001',
            'Contributor_Name' => 'Fake Publisher',
            'Writer_Roles' => 'E',
            'Perform_Share_Owner_Rights' => '50',
            'Controlled_Mechanical_Share' => '50',
        ]),
    ];
}

/**
 * A work with a single writer who owns everything.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function soleWriterRow(int $number, array $overrides = []): array
{
    return importWorkRow($number, [
        'Perform_Share_Owner_Rights' => '100',
        'Controlled_Mechanical_Share' => '100',
        ...$overrides,
    ]);
}

describe('template and field guide', function () {
    test('the template has the headers of the works workbook in order', function () {
        $headers = ImportRegistry::find(CsvImportType::Works)->headers();

        expect(implode(',', $headers))->toBe('TAP_Number,Work_ID,English_Work_Title,Others_Work_Title,ISWC,ISRC,Status,Genre,Language,Duration,Artist_Name,Ori_Work_Title,Sub_Title_Work,Society_No,IPI_No,Contributor_Name,Publisher_Client_ID,Writer_ID,Writer_Roles,Perform_Share_Owner_Rights,Controlled_Mechanical_Share,Controlled_Sync_Share,Controlled_Lyrics_Share,Controlled,Territories_Controlled,Territory_Exclusions,Administration_Start_Date,Administration_End_Date,Registration_Date,Registration_Status,Public_Domain,Song_Link,Remark,Release_Date,COMPASS_Work_Internal_No,COMPASS_Work_Type,COMPASS_Start_Date,WAMI_Work_Internal_No,WAMI_Work_Type,Conflict_Status,Conflict_Notes');

        $template = $this->get(route('imports.template', CsvImportType::Works))->assertOk()->streamedContent();

        expect(trim(ltrim($template, "\xEF\xBB\xBF")))->toBe(implode(',', $headers));
    });

    test('the field guide page lists the columns', function () {
        $this->get(route('imports.create', CsvImportType::Works))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('imports/create')
                ->where('type', 'works')
                ->where('columns.0.header', 'TAP_Number')
                ->where('columns.1.header', 'Work_ID')
                ->where('columns.1.required', true)
            );
    });

    test('the example file is valid when its writers and publisher exist', function () {
        $example = ImportRegistry::find(CsvImportType::Works)->exampleRows();

        uploadCsv($this, CsvImportType::Works, $example);

        expect(CsvImport::query()->firstOrFail())
            ->total_rows->toBe(4)
            ->valid_rows->toBe(4)
            ->error_rows->toBe(0);
    });
});

describe('importing', function () {
    test('rows of one work are imported as one work with its contributors and ISRCs', function () {
        $csvImport = importWorks($this, fakeWorkRows(1, [
            'Status' => 'org',
            'Public_Domain' => 'PD',
            'Conflict_Status' => '',
            'Registration_Status' => 'submitted',
        ]));

        $work = Work::query()->with(['isrcs', 'parties'])->firstOrFail();

        expect(Work::query()->count())->toBe(1)
            ->and($work->tap_number)->toBe('TAP-'.str_pad((string) $work->id, 6, '0', STR_PAD_LEFT))
            ->and($work->macp_work_id)->toBe('MACP-FAKE-1')
            ->and($work->english_title)->toBe('Fake Song 1')
            ->and($work->work_type)->toBe('ORG')
            ->and($work->is_public_domain)->toBeTrue()
            ->and($work->registration_status)->toBe(RegistrationStatus::Submitted)
            ->and($work->conflict_status)->toBe(ConflictStatus::NoConflict)
            ->and($work->isrcs->pluck('isrc')->sort()->values()->all())->toBe(['MYABC2600001', 'MYABC2600002'])
            ->and($work->parties)->toHaveCount(3)
            ->and($work->parties->map(fn ($party) => [$party->writer_id, $party->publisher_id, $party->role, $party->performance_share])->all())
            ->toBe([
                [$this->writer->id, null, PartyRole::C, '25.00'],
                [$this->coWriter->id, null, PartyRole::A, '25.00'],
                [null, $this->publisher->id, PartyRole::E, '50.00'],
            ])
            ->and($work->parties->first())
            ->contributor_name->toBe('Fake Writer')
            ->is_controlled->toBeTrue()
            ->territories_controlled->toBe(['WW'])
            ->and($csvImport)
            ->status->toBe(CsvImportStatus::Imported)
            ->imported_count->toBe(3)
            ->error_rows->toBe(0);
    });

    test('history is written with the import and the uploader', function () {
        $csvImport = importWorks($this, fakeWorkRows(1));

        $entries = RightsHistory::query()->orderBy('id')->get();

        expect($entries->pluck('change_type')->all())->toBe([
            RightsChangeType::WorkCreated,
            RightsChangeType::PartyAdded,
            RightsChangeType::PartyAdded,
            RightsChangeType::PartyAdded,
        ])
            ->and($entries->pluck('csv_import_id')->unique()->all())->toBe([$csvImport->id])
            ->and($entries->pluck('user_id')->unique()->all())->toBe([$this->admin->id])
            ->and($entries->map(fn ($entry) => $entry->effective_date->toDateString())->unique()->all())->toBe(['2026-01-01']);
    });

    test('without an administration start date a party starts on its agreement start date', function () {
        importWorks($this, [soleWriterRow(1, ['Administration_Start_Date' => ''])]);

        expect(Work::query()->firstOrFail()->parties->first()->effective_start_date->toDateString())->toBe('2025-03-01');
    });

    test('a party is found by code, IPI number, pseudonym IPI number or publisher code', function () {
        WriterAlias::factory()->create([
            'writer_id' => $this->coWriter->id,
            'name' => 'Stage Name',
            'ipi_base_number' => '99999999999',
            'position' => 1,
        ]);

        importWorks($this, [
            importWorkRow(1, ['IPI_No' => '', 'Writer_ID' => strtolower($this->writer->writer_code)]),
            importWorkRow(1, ['IPI_No' => '99999999999', 'Contributor_Name' => 'Stage Name', 'Writer_Roles' => 'A']),
            importWorkRow(1, ['IPI_No' => '00000000001', 'Contributor_Name' => 'Fake Publisher', 'Writer_Roles' => 'E']),
            importWorkRow(1, ['IPI_No' => '', 'Publisher_Client_ID' => $this->publisher->publisher_code, 'Contributor_Name' => 'Fake Publisher', 'Writer_Roles' => 'SE']),
        ]);

        $parties = Work::query()->firstOrFail()->parties;

        expect(CsvImport::query()->firstOrFail()->error_rows)->toBe(0)
            ->and($parties->pluck('writer_id')->all())->toBe([$this->writer->id, $this->coWriter->id, null, null])
            ->and($parties->pluck('publisher_id')->all())->toBe([null, null, $this->publisher->id, $this->publisher->id])
            ->and(latestImportIssues('warnings'))->toBe([]);
    });

    test('a contributor name that differs from the record is only a warning', function () {
        importWorks($this, [soleWriterRow(1, ['Contributor_Name' => 'Someone Else'])]);

        $warnings = latestImportIssues('warnings');

        expect($warnings)->toHaveKey('2:Contributor_Name')
            ->and($warnings['2:Contributor_Name'][0])->toContain('Fake Writer')
            ->and(Work::query()->firstOrFail()->parties->first()->contributor_name)->toBe('Someone Else');
    });

    test('several works in one file are imported and only the invalid ones are skipped', function () {
        $csvImport = importWorks($this, [
            ...fakeWorkRows(1),
            soleWriterRow(2, ['Registration_Status' => 'Unknown']),
            soleWriterRow(3),
        ]);

        expect(Work::query()->pluck('macp_work_id')->all())->toBe(['MACP-FAKE-1', 'MACP-FAKE-3'])
            ->and($csvImport)
            ->total_rows->toBe(5)
            ->valid_rows->toBe(4)
            ->error_rows->toBe(1)
            ->imported_count->toBe(4);
    });

    test('a work whose rows continue over a chunk boundary is still one work', function () {
        config(['imports.chunk_size' => 2]);

        importWorks($this, [...fakeWorkRows(1), ...fakeWorkRows(2), soleWriterRow(3)]);

        expect(Work::query()->count())->toBe(3)
            ->and(Work::query()->withCount('parties')->orderBy('id')->pluck('parties_count')->all())->toBe([3, 3, 1])
            ->and(CsvImport::query()->firstOrFail()->error_rows)->toBe(0);
    });

    test('previewing writes nothing', function () {
        uploadCsv($this, CsvImportType::Works, fakeWorkRows(1));

        expect(CsvImport::query()->firstOrFail()->status)->toBe(CsvImportStatus::Previewed)
            ->and(Work::query()->count())->toBe(0)
            ->and(RightsHistory::query()->count())->toBe(0);
    });

    test('a Work_ID taken after the preview is skipped when confirming', function () {
        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1), soleWriterRow(2)]);
        $csvImport = CsvImport::query()->firstOrFail();

        Work::factory()->create(['macp_work_id' => 'MACP-FAKE-1']);

        $this->post(route('imports.confirm', $csvImport));

        expect($csvImport->refresh())
            ->imported_count->toBe(1)
            ->error_rows->toBe(1)
            ->and(Work::query()->pluck('macp_work_id')->all())->toBe(['MACP-FAKE-1', 'MACP-FAKE-2']);
    });
});

describe('rows that are rejected', function () {
    test('a party that cannot be identified is rejected', function (array $overrides, string $column) {
        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1, $overrides)]);

        expect(latestImportIssues())->toHaveKey("2:{$column}")
            ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(0);
    })->with([
        'unknown IPI number' => [['IPI_No' => '00000000099'], 'IPI_No'],
        'unknown writer code' => [['IPI_No' => '', 'Writer_ID' => 'WRT-999999'], 'Writer_ID'],
        'neither a code nor an IPI number' => [['IPI_No' => ''], 'IPI_No'],
        'IPI number of the wrong length' => [['IPI_No' => '123'], 'IPI_No'],
        'publisher code on a writer role' => [['Publisher_Client_ID' => 'PUB-000001'], 'Publisher_Client_ID'],
        'writer code on a publisher role' => [['Writer_Roles' => 'E', 'Writer_ID' => 'WRT-000001', 'IPI_No' => '00000000001'], 'Writer_ID'],
        'a publisher IPI number on a writer role' => [['IPI_No' => '00000000001'], 'IPI_No'],
    ]);

    test('an IPI number held by two writers is ambiguous', function () {
        WriterAlias::factory()->create([
            'writer_id' => $this->coWriter->id,
            'ipi_base_number' => '00000000011',
            'position' => 1,
        ]);

        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1)]);

        expect(latestImportIssues()['2:IPI_No'][0])->toContain('More than one writer');
    });

    test('a deleted writer is not matched', function () {
        $this->writer->delete();

        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1)]);

        expect(latestImportIssues())->toHaveKey('2:IPI_No');
    });

    test('bad values are reported against their column', function (string $header, string $value) {
        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1, [$header => $value])]);

        expect(latestImportIssues())->toHaveKey("2:{$header}");
    })->with([
        'missing Work_ID' => ['Work_ID', ''],
        'missing contributor name' => ['Contributor_Name', ''],
        'bad role' => ['Writer_Roles', 'Singer'],
        'missing performance share' => ['Perform_Share_Owner_Rights', ''],
        'share over 100' => ['Controlled_Mechanical_Share', '120'],
        'share with a percent sign' => ['Controlled_Sync_Share', '10%'],
        'bad controlled flag' => ['Controlled', 'maybe'],
        'bad territory' => ['Territories_Controlled', 'MY|ZZ'],
        'worldwide mixed with a country' => ['Territory_Exclusions', 'WW|MY'],
        'bad registration status' => ['Registration_Status', 'Unknown'],
        'bad public domain flag' => ['Public_Domain', 'maybe'],
        'bad conflict status' => ['Conflict_Status', 'Odd'],
        'bad ISWC' => ['ISWC', 'T123'],
        'bad ISRC' => ['ISRC', 'MYABC2600001|BAD'],
        'bad duration' => ['Duration', '3:30'],
        'bad date' => ['Administration_Start_Date', '01/01/2026'],
        'bad song link' => ['Song_Link', 'nope'],
    ]);

    test('a work needs at least one title', function () {
        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1, ['English_Work_Title' => '', 'Others_Work_Title' => ''])]);

        expect(latestImportIssues())->toHaveKeys(['2:English_Work_Title', '2:Others_Work_Title']);
    });

    test('a Work_ID that is already registered is rejected with its TAP number', function () {
        $existing = Work::factory()->create(['macp_work_id' => 'MACP-FAKE-1']);
        $deleted = Work::factory()->create(['macp_work_id' => 'MACP-FAKE-2']);
        $deleted->delete();

        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1), soleWriterRow(2)]);

        $issues = latestImportIssues();

        expect($issues['2:Work_ID'][0])->toContain($existing->tap_number)
            ->and($issues)->toHaveKey('3:Work_ID');
    });

    test('a TAP number is rejected because updating is not supported', function () {
        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1, ['TAP_Number' => 'TAP-000001'])]);

        expect(latestImportIssues()['2:TAP_Number'][0])->toContain('not supported');
    });

    test('shares that add up to more than 100 reject the whole work', function () {
        $csvImport = importWorks($this, [
            importWorkRow(1, ['Perform_Share_Owner_Rights' => '60']),
            importWorkRow(1, ['IPI_No' => '00000000012', 'Contributor_Name' => 'Another Fake Writer', 'Writer_Roles' => 'A', 'Perform_Share_Owner_Rights' => '60']),
        ]);

        $issues = latestImportIssues();

        expect($issues['2:Perform_Share_Owner_Rights'][0])->toContain('more than 100')
            ->and($issues)->toHaveKey('3:')
            ->and($csvImport)->error_rows->toBe(2)
            ->and(Work::query()->count())->toBe(0);
    });

    test('shares that add up to less than 100 are accepted', function () {
        importWorks($this, [importWorkRow(1)]);

        expect(Work::query()->count())->toBe(1);
    });

    test('the same party twice with the same role is rejected', function () {
        uploadCsv($this, CsvImportType::Works, [importWorkRow(1), importWorkRow(1)]);

        expect(latestImportIssues()['3:Contributor_Name'][0])->toContain('already listed');
    });

    test('rows of one work that disagree about the work are rejected and every row is reported', function () {
        $rows = fakeWorkRows(1);
        $rows[1]['Genre'] = 'Rock';

        $csvImport = importWorks($this, $rows);

        $issues = latestImportIssues();

        expect($issues['3:Genre'][0])->toContain('Must be the same')
            ->and($issues['2:'][0])->toContain('Skipped because line 3')
            ->and($issues['4:'][0])->toContain('Skipped because line 3')
            ->and($csvImport->error_rows)->toBe(3)
            ->and(Work::query()->count())->toBe(0);

        $errorsFile = Storage::disk('local')->get($csvImport->errorsFilePath());

        expect(substr_count($errorsFile, "\n"))->toBe(4)
            ->and($errorsFile)->toContain('Skipped because line 3');
    });

    test('an invalid row never creates a work, party or history entry', function () {
        importWorks($this, [soleWriterRow(1, ['Registration_Status' => 'Unknown'])]);

        expect(Work::query()->count())->toBe(0)
            ->and(RightsHistory::query()->count())->toBe(0);
    });
});

describe('the layout of the file', function () {
    test('a file with the rows of each work together can be confirmed', function () {
        uploadCsv($this, CsvImportType::Works, [...fakeWorkRows(1), ...fakeWorkRows(2)]);

        expect(CsvImport::query()->firstOrFail()->layout_error)->toBeNull();
    });

    test('a work whose rows are apart blocks the confirmation', function () {
        $rows = fakeWorkRows(1);

        uploadCsv($this, CsvImportType::Works, [$rows[0], soleWriterRow(2), $rows[1], $rows[2]]);

        $csvImport = CsvImport::query()->firstOrFail();

        expect($csvImport->layout_error)->toContain('MACP-FAKE-1')->toContain('line 2')->toContain('line 4')
            ->and(latestImportIssues())->toHaveKey('4:Work_ID');

        $this->post(route('imports.confirm', $csvImport))->assertRedirect(route('imports.show', $csvImport));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Previewed)
            ->and(Work::query()->count())->toBe(0);

        $this->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page->where('csvImport.data.layout_error', $csvImport->layout_error));
    });

    test('the number of other separated groups of rows is mentioned', function () {
        $first = fakeWorkRows(1);
        $second = fakeWorkRows(2);

        uploadCsv($this, CsvImportType::Works, [$first[0], $second[0], $first[1], $second[1], $first[2], $second[2]]);

        expect(CsvImport::query()->firstOrFail()->layout_error)->toContain('3 more groups of rows are also apart');
    });

    test('a work that comes back in a later chunk is found', function () {
        config(['imports.chunk_size' => 2]);

        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1), soleWriterRow(2), soleWriterRow(3), soleWriterRow(4), soleWriterRow(2)]);

        expect(CsvImport::query()->firstOrFail()->layout_error)->toContain('MACP-FAKE-2');
    });
});

describe('access', function () {
    test('regular users cannot upload a works file', function () {
        $this->actingAs(User::factory()->create());

        uploadCsv($this, CsvImportType::Works, [soleWriterRow(1)])->assertForbidden();

        expect(CsvImport::query()->count())->toBe(0);
    });
});
