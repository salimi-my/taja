<?php

use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create(['name' => 'Ada Admin']);
});

describe('access', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('imports.index'))->assertRedirect(route('login'));
    });

    test('regular users cannot see the import history', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('imports.index'))
            ->assertForbidden();
    });

    test('admins see the import history page', function () {
        CsvImport::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('imports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('imports/index')
                ->has('csvImports.data', 2)
                ->has('types', 3)
                ->has('statuses', 6)
            );
    });
});

describe('listing', function () {
    test('imports are listed newest first by default', function () {
        $older = CsvImport::factory()->create(['created_at' => now()->subDay()]);
        $newer = CsvImport::factory()->create(['created_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('imports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('csvImports.data.0.id', $newer->id)
                ->where('csvImports.data.1.id', $older->id)
            );
    });

    test('a row has the figures the table shows and no error details', function () {
        CsvImport::factory()->imported()->create([
            'original_filename' => 'batch.csv',
            'total_rows' => 5,
            'valid_rows' => 4,
            'error_rows' => 1,
            'imported_count' => 4,
            'errors' => [['line' => 3, 'column' => 'Name', 'message' => 'Bad.']],
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('imports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('csvImports.data.0.original_filename', 'batch.csv')
                ->where('csvImports.data.0.status', 'imported')
                ->where('csvImports.data.0.total_rows', 5)
                ->where('csvImports.data.0.error_rows', 1)
                ->where('csvImports.data.0.has_errors_file', true)
                ->where('csvImports.data.0.uploaded_by', 'Ada Admin')
                ->where('csvImports.data.0.uploaded_by_avatar_url', $this->admin->avatar_url)
                ->missing('csvImports.data.0.errors')
            );
    });

    test('the list is paginated', function () {
        CsvImport::factory()->count(12)->create();

        $this->actingAs($this->admin)
            ->get(route('imports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 10)
                ->where('csvImports.meta.total', 12)
                ->where('csvImports.meta.last_page', 2)
            );

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('csvImports.data', 2));
    });
});

describe('filters', function () {
    test('the search matches the file name and the uploader', function () {
        $byName = CsvImport::factory()->create(['original_filename' => 'spring-writers.csv']);
        $byUploader = CsvImport::factory()->create([
            'original_filename' => 'other.csv',
            'user_id' => User::factory()->admin()->create(['name' => 'Zed Uploader']),
        ]);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['search' => 'spring']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 1)
                ->where('csvImports.data.0.id', $byName->id)
            );

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['search' => 'Zed']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 1)
                ->where('csvImports.data.0.id', $byUploader->id)
            );
    });

    test('the search treats percent signs literally', function () {
        CsvImport::factory()->create(['original_filename' => 'plain.csv']);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('csvImports.data', 0));
    });

    test('the type and status filters narrow the list', function () {
        $writers = CsvImport::factory()->create(['type' => CsvImportType::Writers]);
        CsvImport::factory()->create(['type' => CsvImportType::Publishers]);
        CsvImport::factory()->cancelled()->create(['type' => CsvImportType::Publishers]);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['types' => ['writers']]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 1)
                ->where('csvImports.data.0.id', $writers->id)
            );

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['status' => [CsvImportStatus::Cancelled->value]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 1)
                ->where('csvImports.data.0.status', 'cancelled')
            );
    });

    test('the date range keeps imports created inside it', function () {
        CsvImport::factory()->create(['created_at' => '2026-01-01 10:00:00']);
        $inside = CsvImport::factory()->create(['created_at' => '2026-02-15 10:00:00']);
        CsvImport::factory()->create(['created_at' => '2026-04-01 10:00:00']);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['start_date' => '2026-02-01', 'end_date' => '2026-02-28']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('csvImports.data', 1)
                ->where('csvImports.data.0.id', $inside->id)
            );
    });
});

describe('sorting and validation', function () {
    test('imports can be sorted by a listed column', function () {
        $small = CsvImport::factory()->create(['total_rows' => 1]);
        $large = CsvImport::factory()->create(['total_rows' => 100]);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['sort' => 'total_rows', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('csvImports.data.0.id', $small->id)
                ->where('csvImports.data.1.id', $large->id)
            );
    });

    test('unknown sort columns, filter values and page sizes are rejected', function (array $query, string $errorKey) {
        $this->actingAs($this->admin)
            ->get(route('imports.index', $query))
            ->assertSessionHasErrors($errorKey);
    })->with([
        'sort' => [['sort' => 'stored_path'], 'sort'],
        'direction' => [['direction' => 'sideways'], 'direction'],
        'per_page' => [['per_page' => 9999], 'per_page'],
        'types' => [['types' => ['invoices']], 'types.0'],
        'status' => [['status' => ['lost']], 'status.0'],
    ]);
});

/**
 * An import with its uploaded file and file of skipped rows on the fake disk.
 *
 * @param  array<string, mixed>  $attributes
 */
function importWithFiles(array $attributes = []): CsvImport
{
    $csvImport = CsvImport::factory()->create($attributes);

    Storage::disk('local')->put($csvImport->stored_path, "Publisher_Name\nFake\n");
    Storage::disk('local')->put($csvImport->errorsFilePath(), "Publisher_Name,Error\nFake,Bad\n");

    return $csvImport;
}

describe('row actions', function () {
    test('a row says whether the original file can be downloaded and the import deleted', function () {
        importWithFiles(['original_filename' => 'kept.csv']);
        CsvImport::factory()->cancelled()->create(['original_filename' => 'gone.csv']);
        importWithFiles(['original_filename' => 'running.csv', 'status' => CsvImportStatus::Importing]);

        $this->actingAs($this->admin)
            ->get(route('imports.index', ['sort' => 'original_filename', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('csvImports.data.0.original_filename', 'gone.csv')
                ->where('csvImports.data.0.has_original_file', false)
                ->where('csvImports.data.0.can_delete', true)
                ->where('csvImports.data.1.original_filename', 'kept.csv')
                ->where('csvImports.data.1.has_original_file', true)
                ->where('csvImports.data.2.original_filename', 'running.csv')
                ->where('csvImports.data.2.can_delete', false)
            );
    });

    test('the original file downloads under the name it was uploaded with', function () {
        $csvImport = importWithFiles(['original_filename' => 'my-publishers.csv']);

        $this->actingAs($this->admin)
            ->get(route('imports.download', $csvImport))
            ->assertOk()
            ->assertDownload('my-publishers.csv');
    });

    test('a file that is gone cannot be downloaded', function () {
        $csvImport = CsvImport::factory()->cancelled()->create();

        $this->actingAs($this->admin)
            ->get(route('imports.download', $csvImport))
            ->assertNotFound();
    });

    test('regular users can neither download nor delete', function () {
        $csvImport = importWithFiles();

        $this->actingAs(User::factory()->create());

        $this->get(route('imports.download', $csvImport))->assertForbidden();
        $this->delete(route('imports.destroy', $csvImport))->assertForbidden();
        $this->delete(route('imports.destroy-many'), ['ids' => [$csvImport->id]])->assertForbidden();

        expect(CsvImport::query()->count())->toBe(1);
    });
});

describe('deleting', function () {
    test('deleting an import removes its record and files but keeps what it created', function () {
        $csvImport = importWithFiles();
        $publisher = Publisher::factory()->create();
        $entry = RightsHistory::factory()->create(['csv_import_id' => $csvImport->id]);

        $this->actingAs($this->admin)
            ->delete(route('imports.destroy', $csvImport))
            ->assertRedirect();

        expect(CsvImport::query()->count())->toBe(0)
            ->and(Publisher::query()->whereKey($publisher->id)->exists())->toBeTrue()
            ->and($entry->fresh()->csv_import_id)->toBeNull();

        Storage::disk('local')->assertMissing($csvImport->stored_path);
        Storage::disk('local')->assertMissing($csvImport->errorsFilePath());
    });

    test('an import that is still running cannot be deleted', function () {
        $csvImport = importWithFiles(['status' => CsvImportStatus::Validating]);

        $this->actingAs($this->admin)
            ->delete(route('imports.destroy', $csvImport))
            ->assertForbidden();

        expect(CsvImport::query()->count())->toBe(1);
        Storage::disk('local')->assertExists($csvImport->stored_path);
    });

    test('several imports can be deleted at once, skipping the ones still running', function () {
        $first = importWithFiles();
        $second = importWithFiles();
        $running = importWithFiles(['status' => CsvImportStatus::Importing]);

        $this->actingAs($this->admin)
            ->delete(route('imports.destroy-many'), ['ids' => [$first->id, $second->id, $running->id]])
            ->assertRedirect();

        expect(CsvImport::query()->pluck('id')->all())->toBe([$running->id]);

        Storage::disk('local')->assertMissing($first->stored_path);
        Storage::disk('local')->assertExists($running->stored_path);
    });

    test('a bulk delete needs existing ids', function () {
        $this->actingAs($this->admin)
            ->delete(route('imports.destroy-many'), [])
            ->assertSessionHasErrors('ids');

        $this->actingAs($this->admin)
            ->delete(route('imports.destroy-many'), ['ids' => [999999]])
            ->assertSessionHasErrors('ids.0');
    });
});
