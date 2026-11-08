<?php

use App\Actions\Imports\ImportRegistry;
use App\Actions\Imports\ProcessCsvImport;
use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use App\Jobs\ProcessCsvImportJob;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CountrySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

describe('access', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('imports.create', CsvImportType::Publishers))->assertRedirect(route('login'));
    });

    test('regular users cannot use any import page', function () {
        $user = User::factory()->create();
        $csvImport = CsvImport::factory()->create();

        $this->actingAs($user);

        $this->get(route('imports.create', CsvImportType::Publishers))->assertForbidden();
        $this->get(route('imports.template', CsvImportType::Publishers))->assertForbidden();
        $this->get(route('imports.example', CsvImportType::Publishers))->assertForbidden();
        uploadCsv($this, CsvImportType::Publishers, [importPublisherRow(1)])->assertForbidden();
        $this->get(route('imports.show', $csvImport))->assertForbidden();
        $this->post(route('imports.confirm', $csvImport))->assertForbidden();
        $this->delete(route('imports.cancel', $csvImport))->assertForbidden();
        $this->get(route('imports.errors', $csvImport))->assertForbidden();

        expect(CsvImport::query()->count())->toBe(1)
            ->and(Publisher::query()->count())->toBe(0);
    });

    test('admins see the upload page with the field guide', function () {
        $this->actingAs($this->admin)
            ->get(route('imports.create', CsvImportType::Publishers))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('imports/create')
                ->where('type', 'publishers')
                ->where('columns.0.header', 'Publisher_Name')
                ->where('columns.0.required', true)
                ->where('maxRows', 2_000_000)
                ->where('maxFileMegabytes', 1024)
            );
    });

    test('an unknown import type is not found', function () {
        $this->actingAs($this->admin)->get(route('imports.create', 'songs'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('imports.template', 'songs'))->assertNotFound();
    });
});

describe('templates', function (): void {
    test('the template is only the header row and matches the column definitions', function (CsvImportType $type) {
        $content = $this->actingAs($this->admin)
            ->get(route('imports.template', $type))
            ->assertOk()
            ->streamedContent();

        $lines = array_filter(explode("\n", str_replace("\xEF\xBB\xBF", '', $content)));

        expect($lines)->toHaveCount(1)
            ->and(str_getcsv(array_values($lines)[0], escape: ''))->toBe(ImportRegistry::find($type)->headers());
    })->with([CsvImportType::Publishers, CsvImportType::Writers]);

    test('the example file is valid and can be imported as it is', function (CsvImportType $type) {
        $content = $this->actingAs($this->admin)
            ->get(route('imports.example', $type))
            ->assertOk()
            ->streamedContent();

        $this->actingAs($this->admin)
            ->post(route('imports.store', $type), [
                'file' => UploadedFile::fake()->createWithContent('example.csv', $content),
            ])
            ->assertRedirect();

        $csvImport = CsvImport::query()->latest('id')->firstOrFail();

        expect($csvImport->total_rows)->toBe(2)
            ->and($csvImport->error_rows)->toBe(0)
            ->and($csvImport->errors)->toBe([]);
    })->with([CsvImportType::Publishers, CsvImportType::Writers]);
});

describe('upload', function (): void {
    test('a preview records the findings and writes no master data', function () {
        $rows = [
            importPublisherRow(1),
            importPublisherRow(2, ['Official_Email' => 'not-an-email']),
        ];

        $response = uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, $rows);

        $csvImport = CsvImport::query()->firstOrFail();

        $response->assertRedirect(route('imports.show', $csvImport));

        expect($csvImport->status)->toBe(CsvImportStatus::Previewed)
            ->and($csvImport->type)->toBe(CsvImportType::Publishers)
            ->and($csvImport->user_id)->toBe($this->admin->id)
            ->and($csvImport->original_filename)->toBe('import.csv')
            ->and($csvImport->total_rows)->toBe(2)
            ->and($csvImport->valid_rows)->toBe(1)
            ->and($csvImport->error_rows)->toBe(1)
            ->and($csvImport->errors)->toHaveCount(1)
            ->and($csvImport->errors[0]['line'])->toBe(3)
            ->and($csvImport->errors[0]['column'])->toBe('Official_Email')
            ->and(Publisher::query()->count())->toBe(0);

        Storage::disk('local')->assertExists($csvImport->stored_path);
    });

    test('the preview page shows the counts', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->get(route('imports.show', $csvImport))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('imports/show')
                ->where('csvImport.data.status', 'previewed')
                ->where('csvImport.data.total_rows', 1)
                ->where('csvImport.data.valid_rows', 1)
                ->where('csvImport.data.errors_total', 0)
            );
    });

    test('the preview page lists the first rows and marks the rows with errors', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [
            importPublisherRow(1),
            importPublisherRow(2, ['Official_Email' => 'not-an-email']),
        ]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page
                ->has('sample.rows', 2)
                ->where('sample.rows.0.line', 2)
                ->where('sample.rows.0.data.Publisher_Name', 'Fake Publisher 1')
                ->where('sample.rows.0.errors', [])
                ->where('sample.rows.0.error_columns', [])
                ->where('sample.rows.1.line', 3)
                ->has('sample.rows.1.errors', 1)
                ->where('sample.rows.1.error_columns', ['Official_Email'])
            );
    });

    test('the preview can show more rows, up to 100', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, array_map(
            fn (int $number): array => importPublisherRow($number),
            range(1, 120),
        ));
        $csvImport = CsvImport::query()->firstOrFail();

        $this->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page->has('sample.rows', 10));

        $this->get(route('imports.show', [$csvImport, 'sample_rows' => 20]))
            ->assertInertia(fn (Assert $page) => $page->has('sample.rows', 20));

        $this->get(route('imports.show', [$csvImport, 'sample_rows' => 500]))
            ->assertInertia(fn (Assert $page) => $page->has('sample.rows', 100));

        $this->get(route('imports.show', [$csvImport, 'sample_rows' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('sample.rows', 10));
    });

    test('the sample is only sent while the import is previewed', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();
        $csvImport->update(['status' => CsvImportStatus::Cancelled]);

        $this->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page->where('sample', null));
    });

    test('a missing required column rejects the whole file', function () {
        $headers = array_values(array_diff(
            ImportRegistry::find(CsvImportType::Publishers)->headers(),
            ['Company_Registration_Number', 'Status'],
        ));

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)], $headers)
            ->assertSessionHasErrors(['file' => 'The file is missing required column(s): Company_Registration_Number, Status.']);

        expect(CsvImport::query()->count())->toBe(0)
            ->and(Storage::disk('local')->allFiles())->toBe([]);
    });

    test('a file over the row limit fails with a message', function () {
        config(['imports.max_rows' => 3]);

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, array_map(
            fn (int $number): array => importPublisherRow($number),
            range(1, 4),
        ));

        $csvImport = CsvImport::query()->firstOrFail();

        expect($csvImport->status)->toBe(CsvImportStatus::Failed)
            ->and($csvImport->failure_message)->toContain('more than 3 data rows');
    });

    test('a file without data rows is rejected', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [])
            ->assertSessionHasErrors(['file' => 'The file has no data rows.']);
    });

    test('a file that is not a csv is rejected', function () {
        $this->actingAs($this->admin)
            ->post(route('imports.store', CsvImportType::Publishers), [
                'file' => UploadedFile::fake()->create('import.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        expect(CsvImport::query()->count())->toBe(0);
    });

    test('headers are matched ignoring case and a BOM is tolerated', function () {
        $headers = array_map('strtolower', ImportRegistry::find(CsvImportType::Publishers)->headers());
        $row = array_change_key_case(importPublisherRow(1));

        $this->actingAs($this->admin)
            ->post(route('imports.store', CsvImportType::Publishers), [
                'file' => UploadedFile::fake()->createWithContent('import.csv', "\xEF\xBB\xBF".csvContent($headers, [$row])),
            ])
            ->assertSessionHasNoErrors();

        $csvImport = CsvImport::query()->firstOrFail();

        expect($csvImport->valid_rows)->toBe(1)
            ->and($csvImport->error_rows)->toBe(0)
            ->and($csvImport->warnings)->toBe([]);
    });

    test('an unknown column is ignored with a warning', function () {
        $headers = [...ImportRegistry::find(CsvImportType::Publishers)->headers(), 'Favourite_Colour'];

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)], $headers);

        $csvImport = CsvImport::query()->firstOrFail();

        expect($csvImport->valid_rows)->toBe(1)
            ->and($csvImport->warnings)->toBe([[
                'line' => 1,
                'column' => 'Favourite_Colour',
                'message' => 'Unknown column, it is ignored.',
            ]]);
    });
});

describe('confirm', function (): void {
    test('only the valid rows are imported', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [
            importPublisherRow(1),
            importPublisherRow(2, ['Status' => 'Unknown']),
            importPublisherRow(3),
        ]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->post(route('imports.confirm', $csvImport))->assertRedirect(route('imports.show', $csvImport));

        $csvImport->refresh();

        expect($csvImport->status)->toBe(CsvImportStatus::Imported)
            ->and($csvImport->imported_count)->toBe(2)
            ->and($csvImport->imported_at)->not->toBeNull()
            ->and(Publisher::query()->orderBy('id')->pluck('name')->all())->toBe(['Fake Publisher 1', 'Fake Publisher 3'])
            ->and(Publisher::query()->first()->publisher_code)->toStartWith('PUB-');
    });

    test('a row that became a duplicate since the preview is skipped and reported', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1), importPublisherRow(2)]);
        $csvImport = CsvImport::query()->firstOrFail();

        Publisher::factory()->create(['company_registration_number' => 'REG-2']);

        $this->post(route('imports.confirm', $csvImport));

        $csvImport->refresh();

        expect($csvImport->imported_count)->toBe(1)
            ->and($csvImport->error_rows)->toBe(1)
            ->and($csvImport->errors[0]['line'])->toBe(3)
            ->and(Publisher::query()->where('name', 'Fake Publisher 2')->exists())->toBeFalse();
    });

    test('an import cannot be confirmed twice', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->post(route('imports.confirm', $csvImport));
        $this->post(route('imports.confirm', $csvImport));

        expect(Publisher::query()->count())->toBe(1)
            ->and($csvImport->refresh()->imported_count)->toBe(1);
    });

    test('a cancelled import cannot be confirmed', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->delete(route('imports.cancel', $csvImport))->assertRedirect(route('imports.create', CsvImportType::Publishers));
        $this->post(route('imports.confirm', $csvImport));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Cancelled)
            ->and(Publisher::query()->count())->toBe(0);

        Storage::disk('local')->assertMissing($csvImport->stored_path);
    });

    test('confirming reads the stored file and not the browser', function () {
        $csvImport = CsvImport::factory()->create(['stored_path' => 'imports/missing.csv']);

        $this->actingAs($this->admin)->post(route('imports.confirm', $csvImport));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Previewed)
            ->and(Publisher::query()->count())->toBe(0);
    });
});

describe('errors file', function (): void {
    test('contains the original columns of the skipped rows plus an Error column', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [
            importPublisherRow(1),
            importPublisherRow(2, ['Official_Email' => 'not-an-email']),
        ]);
        $csvImport = CsvImport::query()->firstOrFail();

        $content = $this->get(route('imports.errors', $csvImport))->assertOk()->streamedContent();

        $lines = array_values(array_filter(explode("\n", str_replace("\xEF\xBB\xBF", '', $content))));

        expect($lines)->toHaveCount(2)
            ->and(str_getcsv($lines[0], escape: ''))->toBe([...ImportRegistry::find(CsvImportType::Publishers)->headers(), 'Error'])
            ->and($lines[1])->toContain('Fake Publisher 2')
            ->and($lines[1])->toContain('Official_Email: ')
            ->and($lines[1])->not->toContain('Fake Publisher 1');
    });
});

describe('large files', function (): void {
    test('the file is validated and imported by queued jobs', function () {
        Queue::fake();

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();

        Queue::assertPushed(ProcessCsvImportJob::class, fn (ProcessCsvImportJob $job) => ! $job->commit && $job->csvImport->is($csvImport));

        expect($csvImport->status)->toBe(CsvImportStatus::Validating)
            ->and($csvImport->status->isProcessing())->toBeTrue();

        (new ProcessCsvImportJob($csvImport, commit: false))->handle(app(ProcessCsvImport::class));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Previewed)
            ->and($csvImport->progress)->toBe(100);

        $this->post(route('imports.confirm', $csvImport))->assertRedirect(route('imports.show', $csvImport));

        Queue::assertPushed(ProcessCsvImportJob::class, fn (ProcessCsvImportJob $job) => $job->commit);

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Importing)
            ->and(Publisher::query()->count())->toBe(0);

        (new ProcessCsvImportJob($csvImport, commit: true))->handle(app(ProcessCsvImport::class));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Imported)
            ->and(Publisher::query()->count())->toBe(1);
    });

    test('rows are handled in chunks and counted across them', function () {
        config(['imports.chunk_size' => 3]);

        $rows = array_map(fn (int $number): array => importPublisherRow($number), range(1, 10));
        $rows[4]['Official_Email'] = 'nope';
        $rows[8]['Status'] = 'nope';

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, $rows);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->post(route('imports.confirm', $csvImport));

        expect($csvImport->refresh())
            ->total_rows->toBe(10)
            ->valid_rows->toBe(8)
            ->error_rows->toBe(2)
            ->imported_count->toBe(8)
            ->and(Publisher::query()->count())->toBe(8)
            ->and(array_column($csvImport->errors, 'line'))->toBe([6, 10]);
    });

    test('a value repeated in a later chunk is found', function () {
        config(['imports.chunk_size' => 2]);

        $rows = array_map(fn (int $number): array => importPublisherRow($number), range(1, 6));
        $rows[5]['Company_Registration_Number'] = $rows[0]['Company_Registration_Number'];
        $rows[4]['IPI_Name_Number'] = $rows[1]['IPI_Name_Number'];

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, $rows);

        $issues = latestImportIssues();

        expect($issues)->toHaveKeys(['7:Company_Registration_Number', '6:IPI_Name_Number'])
            ->and($issues['7:Company_Registration_Number'][0])->toContain('line 2')
            ->and($issues['6:IPI_Name_Number'][0])->toContain('line 3')
            ->and(CsvImport::query()->firstOrFail()->valid_rows)->toBe(4);
    });

    test('the scratch values are removed once a pass ends', function () {
        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1), importPublisherRow(2)]);
        $csvImport = CsvImport::query()->firstOrFail();

        expect(DB::table('csv_import_values')->count())->toBe(0);

        $this->post(route('imports.confirm', $csvImport));

        expect(DB::table('csv_import_values')->count())->toBe(0);
    });

    test('only the first issues are kept but the errors file has every row', function () {
        config(['imports.max_listed_issues' => 2]);

        $rows = array_map(fn (int $number): array => importPublisherRow($number, ['Official_Email' => 'nope']), range(1, 5));

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, $rows);
        $csvImport = CsvImport::query()->firstOrFail();

        expect($csvImport->errors)->toHaveCount(2)
            ->and($csvImport->error_count)->toBe(5)
            ->and($csvImport->error_rows)->toBe(5);

        $this->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImport.data.errors', 2)
                ->where('csvImport.data.errors_total', 5)
                ->where('csvImport.data.has_errors_file', true)
            );

        $content = $this->get(route('imports.errors', $csvImport))->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(6);
    });

    test('a cancelled import stays cancelled when its queued job runs later', function () {
        Queue::fake();

        uploadCsv($this->actingAs($this->admin), CsvImportType::Publishers, [importPublisherRow(1)]);
        $csvImport = CsvImport::query()->firstOrFail();

        $this->delete(route('imports.cancel', $csvImport));

        (new ProcessCsvImportJob($csvImport, commit: false))->handle(app(ProcessCsvImport::class));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Cancelled)
            ->and($csvImport->failure_message)->toBeNull();
    });

    test('a crashed job marks the import as failed', function (bool $commit, CsvImportStatus $status, string $message) {
        $csvImport = CsvImport::factory()->create(['status' => $status]);

        (new ProcessCsvImportJob($csvImport, $commit))->failed(new RuntimeException('Boom'));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Failed)
            ->and($csvImport->failure_message)->toContain($message);
    })->with([
        'validating' => [false, CsvImportStatus::Validating, 'could not be validated'],
        'importing' => [true, CsvImportStatus::Importing, 'Some rows may already have been imported'],
    ]);

    test('an import that is being imported cannot be cancelled', function () {
        $csvImport = CsvImport::factory()->create(['status' => CsvImportStatus::Importing]);

        $this->actingAs($this->admin)->delete(route('imports.cancel', $csvImport));

        expect($csvImport->refresh()->status)->toBe(CsvImportStatus::Importing);
    });

    test('the show page reports progress while the file is processed', function () {
        $csvImport = CsvImport::factory()->create(['status' => CsvImportStatus::Importing, 'progress' => 42]);

        $this->actingAs($this->admin)
            ->get(route('imports.show', $csvImport))
            ->assertInertia(fn (Assert $page) => $page
                ->where('csvImport.data.is_processing', true)
                ->where('csvImport.data.progress', 42)
            );
    });
});
