<?php

use App\Actions\Masters\SummarizeMasterHistory;
use App\Actions\Masters\UpdatePublisher;
use App\Actions\Masters\UpdateWriter;
use App\Enums\CsvImportType;
use App\Enums\MasterChangeType;
use App\Enums\MasterStatus;
use App\Models\CsvImport;
use App\Models\MasterHistory;
use App\Models\Publisher;
use App\Models\User;
use App\Models\WorkParty;
use App\Models\Writer;
use Database\Seeders\CountrySeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seed(CountrySeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function masterWriterForm(array $overrides = []): array
{
    return [
        'name' => 'Fake History Writer',
        'cmo_society' => 'MACP',
        'status' => 'active',
        'agreement_start_date' => '2026-01-01',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function masterPublisherForm(array $overrides = []): array
{
    return [
        'name' => 'Fake History Tunes Sdn Bhd',
        'rights_types_granted' => ['performance'],
        'company_registration_number' => '202001000777',
        'registration_address' => '1 Fake Street, Kuala Lumpur',
        'official_email' => 'office@fakehistory.test',
        'society_cmo' => 'MACP',
        'status' => 'active',
        'agreement_start_date' => '2026-01-01',
        'territories_granted' => 'my',
        ...$overrides,
    ];
}

describe('the log', function () {
    test('entries cannot be updated or deleted', function () {
        $entry = MasterHistory::factory()->create();

        expect(fn () => $entry->update(['remarks' => 'Changed.']))->toThrow(LogicException::class)
            ->and(fn () => $entry->delete())->toThrow(LogicException::class);
    });

    test('entries are found through the writer and the publisher, newest first', function () {
        $writer = Writer::factory()->create();
        $publisher = Publisher::factory()->create();
        $older = MasterHistory::factory()->forSubject($writer)->create(['recorded_at' => now()->subDay()]);
        $newer = MasterHistory::factory()->forSubject($writer)->create(['recorded_at' => now()]);
        MasterHistory::factory()->forSubject($publisher)->create();

        expect($writer->histories->pluck('id')->all())->toBe([$newer->id, $older->id])
            ->and($publisher->histories)->toHaveCount(1)
            ->and($newer->subject->is($writer))->toBeTrue();
    });
});

describe('writers', function () {
    test('creating a writer logs who created it and its filled-in values', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), masterWriterForm(['aliases' => [['name' => 'Stage Name', 'ipi_base_number' => '']]]))
            ->assertSessionHasNoErrors();

        $writer = Writer::query()->firstOrFail();
        $entry = MasterHistory::query()->sole();

        expect($entry->change_type)->toBe(MasterChangeType::Created)
            ->and($entry->subject_type)->toBe('writer')
            ->and($entry->subject_id)->toBe($writer->id)
            ->and($entry->subject_code)->toBe($writer->writer_code)
            ->and($entry->subject_name)->toBe('Fake History Writer')
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->previous_snapshot)->toBeNull()
            ->and($entry->new_snapshot)->toMatchArray(['name' => 'Fake History Writer', 'writer_code' => $writer->writer_code, 'status' => 'active'])
            ->and($entry->new_snapshot['aliases'])->toBe([['name' => 'Stage Name', 'ipi_base_number' => null]])
            ->and($entry->new_snapshot)->not->toHaveKey('nric');
    });

    test('an invalid writer logs nothing', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), masterWriterForm(['name' => '']))
            ->assertSessionHasErrors('name');

        expect(MasterHistory::query()->count())->toBe(0);
    });

    test('updating a writer logs only the fields that changed', function () {
        $writer = Writer::factory()->withoutOptionalFields()->create(['name' => 'Before Name', 'status' => MasterStatus::Active]);

        app(UpdateWriter::class)($writer, ['name' => 'After Name', 'status' => 'inactive'], $this->admin->id);

        $entry = MasterHistory::query()->sole();

        expect($entry->change_type)->toBe(MasterChangeType::Updated)
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->subject_name)->toBe('After Name')
            ->and($entry->previous_snapshot)->toBe(['name' => 'Before Name', 'status' => 'active'])
            ->and($entry->new_snapshot)->toBe(['name' => 'After Name', 'status' => 'inactive'])
            ->and(app(SummarizeMasterHistory::class)($entry))
            ->toBe('Writer details changed: name from Before Name to After Name, status from active to inactive.');
    });

    test('the update form logs the change with the user', function () {
        $writer = Writer::factory()->withoutOptionalFields()->create(['name' => 'Before Name']);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), masterWriterForm([
                'name' => 'After Name',
                'cmo_society' => $writer->cmo_society,
                'status' => $writer->status->value,
                'agreement_start_date' => $writer->agreement_start_date->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $entry = MasterHistory::query()->sole();

        expect($entry->new_snapshot)->toBe(['name' => 'After Name'])
            ->and($entry->user_id)->toBe($this->admin->id);
    });

    test('saving a writer without changes logs nothing', function () {
        $writer = Writer::factory()->withoutOptionalFields()->withAliases(1)->create();

        app(UpdateWriter::class)($writer, [
            'name' => $writer->name,
            'aliases' => $writer->aliases->map(fn ($alias) => ['name' => $alias->name, 'ipi_base_number' => $alias->ipi_base_number])->all(),
        ], $this->admin->id);

        expect(MasterHistory::query()->count())->toBe(0);
    });

    test('renaming a writer renames its party rows but keeps a custom name', function () {
        $writer = Writer::factory()->create(['name' => 'Fake Old Writer']);
        $party = WorkParty::factory()->forWriter($writer)->create();
        $custom = WorkParty::factory()->forWriter($writer)->create(['contributor_name' => 'Fake Stage Name']);

        app(UpdateWriter::class)($writer, ['name' => 'Fake New Writer'], $this->admin->id);

        expect($party->refresh()->contributor_name)->toBe('Fake New Writer')
            ->and($custom->refresh()->contributor_name)->toBe('Fake Stage Name');
    });

    test('pseudonym changes are logged as one list', function () {
        $writer = Writer::factory()->withoutOptionalFields()->create();

        app(UpdateWriter::class)($writer, ['aliases' => [['name' => 'First Alias'], ['name' => 'Second Alias', 'ipi_base_number' => 'I-000000001-1']]], $this->admin->id);
        app(UpdateWriter::class)($writer, ['aliases' => [['name' => 'First Alias']]], $this->admin->id);

        [$added, $removed] = MasterHistory::query()->orderBy('id')->get()->all();

        expect($added->previous_snapshot)->toBe(['aliases' => []])
            ->and($added->new_snapshot['aliases'])->toHaveCount(2)
            ->and($removed->new_snapshot['aliases'])->toBe([['name' => 'First Alias', 'ipi_base_number' => null]])
            ->and(app(SummarizeMasterHistory::class)($added))->toContain('Second Alias (I-000000001-1)');
    });

    test('a publisher link change is logged with the publisher name', function () {
        $publisher = Publisher::factory()->create(['name' => 'Linked Fake Music']);
        $writer = Writer::factory()->withoutOptionalFields()->create();

        app(UpdateWriter::class)($writer, ['publisher_id' => $publisher->id], $this->admin->id);

        $entry = MasterHistory::query()->sole();

        expect($entry->previous_snapshot)->toBe(['publisher' => null])
            ->and($entry->new_snapshot['publisher'])->toStartWith('Linked Fake Music (PUB-');
    });

    test('deleting a writer logs its last state and the entry survives', function () {
        $writer = Writer::factory()->create(['name' => 'Gone Fake Writer']);

        $this->actingAs($this->admin)->delete(route('writers.destroy', $writer))->assertRedirect(route('writers.index'));

        $entry = MasterHistory::query()->sole();

        expect($entry->change_type)->toBe(MasterChangeType::Deleted)
            ->and($entry->previous_snapshot['name'])->toBe('Gone Fake Writer')
            ->and($entry->new_snapshot)->toBeNull()
            ->and($entry->subject->trashed())->toBeTrue()
            ->and($entry->user_id)->toBe($this->admin->id);
    });

    test('a writer that cannot be deleted logs nothing', function () {
        $writer = Writer::factory()->create();
        WorkParty::factory()->forWriter($writer)->create();

        $this->actingAs($this->admin)->delete(route('writers.destroy', $writer));

        expect($writer->fresh())->not->toBeNull()
            ->and(MasterHistory::query()->count())->toBe(0);
    });
});

describe('publishers', function () {
    test('creating, updating and deleting a publisher are logged in order', function () {
        $this->actingAs($this->admin)->post(route('publishers.store'), masterPublisherForm())->assertSessionHasNoErrors();

        $publisher = Publisher::query()->firstOrFail();

        $this->put(route('publishers.update', $publisher), masterPublisherForm(['status' => 'inactive']))->assertSessionHasNoErrors();
        $this->delete(route('publishers.destroy', $publisher))->assertRedirect(route('publishers.index'));

        $entries = $publisher->histories()->get()->reverse()->values();

        expect($entries->pluck('change_type')->all())->toBe([MasterChangeType::Created, MasterChangeType::Updated, MasterChangeType::Deleted])
            ->and($entries->pluck('user_id')->unique()->all())->toBe([$this->admin->id])
            ->and($entries[0]->new_snapshot)->toMatchArray(['name' => 'Fake History Tunes Sdn Bhd', 'publisher_code' => $publisher->publisher_code])
            ->and($entries[1]->new_snapshot)->toBe(['status' => 'inactive'])
            ->and($entries[1]->previous_snapshot)->toBe(['status' => 'active'])
            ->and($entries[2]->previous_snapshot['name'])->toBe('Fake History Tunes Sdn Bhd');
    });

    test('rights types and territories are logged as lists', function () {
        $publisher = Publisher::factory()->withoutOptionalFields()->create(['rights_types_granted' => ['performance'], 'territories_granted' => ['MY']]);

        app(UpdatePublisher::class)($publisher, ['rights_types_granted' => ['performance', 'sync'], 'territories_granted' => ['MY', 'SG']], $this->admin->id);

        $entry = MasterHistory::query()->sole();

        expect($entry->previous_snapshot)->toBe(['rights_types_granted' => ['performance'], 'territories_granted' => ['MY']])
            ->and($entry->new_snapshot)->toBe(['rights_types_granted' => ['performance', 'sync'], 'territories_granted' => ['MY', 'SG']]);
    });

    test('renaming a publisher renames its party rows but keeps a custom name', function () {
        $publisher = Publisher::factory()->create(['name' => 'Fake Old Name Sdn Bhd']);
        $current = WorkParty::factory()->forPublisher($publisher)->create();
        $ended = WorkParty::factory()->forPublisher($publisher)->ended()->create();
        $custom = WorkParty::factory()->forPublisher($publisher)->create(['contributor_name' => 'Fake Imprint Name']);
        $other = WorkParty::factory()->forPublisher()->create();

        app(UpdatePublisher::class)($publisher, ['name' => 'Fake New Name Sdn Bhd'], $this->admin->id);

        expect($current->refresh()->contributor_name)->toBe('Fake New Name Sdn Bhd')
            ->and($ended->refresh()->contributor_name)->toBe('Fake New Name Sdn Bhd')
            ->and($custom->refresh()->contributor_name)->toBe('Fake Imprint Name')
            ->and($other->refresh()->contributor_name)->not->toBe('Fake New Name Sdn Bhd');
    });

    test('saving a publisher without changes logs nothing', function () {
        $publisher = Publisher::factory()->create();

        app(UpdatePublisher::class)($publisher, ['name' => $publisher->name], $this->admin->id);

        expect(MasterHistory::query()->count())->toBe(0);
    });
});

describe('imports', function () {
    beforeEach(function () {
        Storage::fake('local');
        $this->actingAs($this->admin);
    });

    test('imported writers and publishers log an imported entry tied to the import', function (CsvImportType $type, string $subjectType) {
        $row = $type === CsvImportType::Writers
            ? importWriterRow(1, ['Pseudonym_Stage_Name_1' => 'Stage One'])
            : importPublisherRow(1);

        uploadCsv($this, $type, [$row]);

        $csvImport = CsvImport::query()->latest('id')->firstOrFail();
        $this->post(route('imports.confirm', $csvImport));

        $entry = MasterHistory::query()->sole();

        expect($entry->change_type)->toBe(MasterChangeType::Imported)
            ->and($entry->subject_type)->toBe($subjectType)
            ->and($entry->csv_import_id)->toBe($csvImport->id)
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->subject_code)->not->toBeNull()
            ->and($entry->new_snapshot['name'])->toBe($entry->subject_name)
            ->and(app(SummarizeMasterHistory::class)($entry))->toContain('imported');
    })->with([
        'writers' => [CsvImportType::Writers, 'writer'],
        'publishers' => [CsvImportType::Publishers, 'publisher'],
    ]);
});

describe('show pages', function () {
    test('the writer page loads its history newest first, 15 entries a page', function () {
        $writer = Writer::factory()->create();
        MasterHistory::factory()->forSubject($writer)->count(16)->create(['recorded_at' => now()->subDay()]);
        $newest = MasterHistory::factory()->forSubject($writer)->create(['recorded_at' => now()]);
        MasterHistory::factory()->forSubject(Publisher::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('writers.show', $writer))
            ->assertInertia(fn (Assert $page) => $page
                ->component('writers/show')
                ->missing('history')
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->has('history.data', 15)
                    ->where('history.data.0.id', $newest->id)
                    ->where('history.meta.total', 17)
                    ->where('history.data.0.subject.type', 'writer')
                )
            );
    });

    test('the publisher page loads only its own history', function () {
        $publisher = Publisher::factory()->create();
        $entry = MasterHistory::factory()->forSubject($publisher)->create(['change_type' => MasterChangeType::Updated, 'user_id' => $this->admin->id]);
        MasterHistory::factory()->forSubject(Writer::factory()->create(['id' => $publisher->id]))->create();

        $this->actingAs(User::factory()->create())
            ->get(route('publishers.show', $publisher))
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishers/show')
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->has('history.data', 1)
                    ->where('history.data.0.id', $entry->id)
                    ->where('history.data.0.change_type_label', 'Updated')
                    ->where('history.data.0.user_name', $this->admin->name)
                )
            );
    });
});
