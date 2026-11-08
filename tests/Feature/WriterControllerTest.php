<?php

use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Database\Seeders\CountrySeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid set of form values for creating a writer.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function writerPayload(array $overrides = []): array
{
    return [
        'name' => 'Fake Writer',
        'cmo_society' => 'MACP',
        'status' => 'active',
        'agreement_start_date' => '2026-01-01',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seed(CountrySeeder::class);
});

describe('index', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('writers.index'))->assertRedirect(route('login'));
    });

    test('regular users can view the list', function () {
        Writer::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('writers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('writers/index')
                ->has('writers.data', 3)
            );
    });

    test('shows the first page newest first with the option lists', function () {
        $publisher = Publisher::factory()->create();
        Writer::factory()->count(12)->create(['cmo_society' => 'MACP']);

        $this->actingAs($this->admin)
            ->get(route('writers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 10)
                ->where('writers.meta.total', 12)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->where('societies', ['MACP'])
                ->where('publishers.0.id', $publisher->id)
                ->has('roles', 5)
                ->has('rightsTypes', 5)
                ->has('statuses', 2)
            );
    });

    test('only offers writer roles', function () {
        $this->actingAs($this->admin)
            ->get(route('writers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles.0.value', PartyRole::C->value)
                ->where('roles', fn ($roles) => collect($roles)->pluck('value')->doesntContain('E'))
            );
    });

    test('does not send private fields in the list', function () {
        Writer::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data.0', fn (Assert $row) => $row
                    ->missing('nric')
                    ->missing('bank_account_number')
                    ->missing('heir_name')
                    ->missing('date_of_birth')
                    ->missing('address')
                    ->etc()
                )
            );
    });

    test('writers can be searched', function (string $term) {
        Writer::factory()->create([
            'name' => 'Needle Writer',
            'ipi_name_number' => '11111111111',
            'ipi_base_number' => 'I-222222222-2',
            'nric' => 'NRIC-333',
        ]);
        Writer::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.name', 'Needle Writer')
            );
    })->with([
        'name' => 'needle',
        'ipi name number' => '1111111',
        'ipi base number' => 'I-222222222',
        'nric' => 'NRIC-333',
    ]);

    test('writers can be searched by an nric typed with dashes', function () {
        Writer::factory()->create(['name' => 'Needle Writer', 'nric' => '900101-01-1234']);
        Writer::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['search' => '900101-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.name', 'Needle Writer')
            );
    });

    test('writers can be searched by their code', function () {
        $writer = Writer::factory()->create();
        Writer::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['search' => $writer->writer_code]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.writer_code', 'WRT-000001')
            );
    });

    test('writers can be searched by a pseudonym or its ipi number', function (string $term) {
        $writer = Writer::factory()->create();
        $writer->aliases()->create(['name' => 'Stage Needle', 'ipi_base_number' => '99988877766', 'position' => 1]);
        Writer::factory()->count(2)->withAliases(1)->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.id', $writer->id)
                ->where('writers.data.0.aliases.0.name', 'Stage Needle')
            );
    })->with([
        'pseudonym' => 'stage needle',
        'pseudonym ipi number' => '9998887',
    ]);

    test('writers can be filtered by status', function () {
        Writer::factory()->count(2)->create();
        Writer::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['status' => [MasterStatus::Inactive->value]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.status', 'inactive')
            );
    });

    test('writers can be filtered by society', function () {
        Writer::factory()->create(['cmo_society' => 'MACP']);
        Writer::factory()->create(['cmo_society' => 'WAMI']);
        Writer::factory()->create(['cmo_society' => 'Non-Member']);

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['societies' => ['MACP', 'WAMI']]))
            ->assertInertia(fn (Assert $page) => $page->has('writers.data', 2));
    });

    test('writers can be filtered by publisher', function () {
        $publisher = Publisher::factory()->create();
        Writer::factory()->withPublisher($publisher)->create();
        Writer::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['publishers' => [$publisher->id]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 1)
                ->where('writers.data.0.publisher.name', $publisher->name)
            );
    });

    test('filters can be combined', function () {
        Writer::factory()->create(['cmo_society' => 'MACP']);
        Writer::factory()->inactive()->create(['cmo_society' => 'MACP']);
        Writer::factory()->create(['cmo_society' => 'WAMI']);

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['societies' => ['MACP'], 'status' => ['active']]))
            ->assertInertia(fn (Assert $page) => $page->has('writers.data', 1));
    });

    test('writers can be sorted by a column', function () {
        Writer::factory()->create(['name' => 'Zed Writer']);
        Writer::factory()->create(['name' => 'Aaron Writer']);

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page->where('writers.data.0.name', 'Aaron Writer'));

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertInertia(fn (Assert $page) => $page->where('writers.data.0.name', 'Zed Writer'));
    });

    test('every column shown in the table can be sorted', function (string $column) {
        $this->actingAs($this->admin)
            ->get(route('writers.index', ['sort' => $column]))
            ->assertOk()
            ->assertSessionHasNoErrors();
    })->with(['name', 'writer_code', 'cmo_society', 'status', 'agreement_start_date', 'created_at']);

    test('rejects a column that cannot be sorted', function () {
        $this->actingAs($this->admin)
            ->get(route('writers.index', ['sort' => 'bank_name']))
            ->assertSessionHasErrors('sort');
    });

    test('the page size and page number are respected', function () {
        Writer::factory()->count(27)->create();

        $this->actingAs($this->admin)
            ->get(route('writers.index', ['per_page' => 25, 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('writers.data', 2)
                ->where('writers.meta.current_page', 2)
            );
    });
});

describe('show', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('writers.show', Writer::factory()->create()))
            ->assertRedirect(route('login'));
    });

    test('regular users can see every field of a writer with its aliases and publisher', function () {
        $publisher = Publisher::factory()->create();
        $writer = Writer::factory()->withPublisher($publisher)->withAliases(2)->create(['bank_name' => 'Fake Bank']);

        $this->actingAs(User::factory()->create())
            ->get(route('writers.show', $writer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('writers/show')
                ->where('writer.data.writer_code', 'WRT-000001')
                ->where('writer.data.bank_name', 'Fake Bank')
                ->where('writer.data.publisher.id', $publisher->id)
                ->where('writer.data.publisher_id', $publisher->id)
                ->has('writer.data.aliases', 2)
                ->where('writer.data.aliases.1.position', 2)
            );
    });

    test('lists the works the writer is a party of today', function () {
        $writer = Writer::factory()->create();
        $work = Work::factory()->create(['english_title' => 'Fake Song']);
        $current = WorkParty::factory()->forWriter($writer)->create(['work_id' => $work->id]);
        WorkParty::factory()->forWriter($writer)->ended()->create(['work_id' => Work::factory()]);
        WorkParty::factory()->forWriter($writer)->superseded()->create(['work_id' => Work::factory()]);
        WorkParty::factory()->create(['work_id' => $work->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('writers.show', $writer))
            ->assertInertia(fn (Assert $page) => $page
                ->where('includeEnded', false)
                ->has('catalogue.data', 1)
                ->where('catalogue.data.0.id', $current->id)
                ->where('catalogue.data.0.work.title', 'Fake Song')
                ->where('catalogue.data.0.work.tap_number', $work->tap_number)
            );
    });

    test('the catalogue can include ended involvement but never superseded rows', function () {
        $writer = Writer::factory()->create();
        WorkParty::factory()->forWriter($writer)->create(['work_id' => Work::factory()]);
        WorkParty::factory()->forWriter($writer)->ended()->create(['work_id' => Work::factory()]);
        WorkParty::factory()->forWriter($writer)->superseded()->create(['work_id' => Work::factory()]);

        $this->actingAs($this->admin)
            ->get(route('writers.show', ['writer' => $writer, 'include_ended' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('includeEnded', true)
                ->has('catalogue.data', 2)
            );
    });

    test('the catalogue leaves out deleted works', function () {
        $writer = Writer::factory()->create();
        $work = Work::factory()->create();
        WorkParty::factory()->forWriter($writer)->create(['work_id' => $work->id]);
        $work->delete();

        $this->actingAs($this->admin)
            ->get(route('writers.show', $writer))
            ->assertInertia(fn (Assert $page) => $page->has('catalogue.data', 0));
    });

    test('a deleted writer is not found', function () {
        $writer = Writer::factory()->create();
        $writer->delete();

        $this->actingAs($this->admin)
            ->get(route('writers.show', $writer))
            ->assertNotFound();
    });

    test('still shows a publisher that was deleted', function () {
        $publisher = Publisher::factory()->create();
        $writer = Writer::factory()->withPublisher($publisher)->create();
        $publisher->delete();

        $this->actingAs($this->admin)
            ->get(route('writers.show', $writer))
            ->assertInertia(fn (Assert $page) => $page->where('writer.data.publisher.name', $publisher->name));
    });
});

describe('create', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('writers.create'))->assertRedirect(route('login'));
    });

    test('admins see the create page with the option lists', function () {
        $this->actingAs($this->admin)
            ->get(route('writers.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('writers/create')
                ->has('rightsTypes', 5)
                ->has('statuses', 2)
                ->has('roles', 5)
                ->has('countries', 249)
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('writers.create'))
            ->assertForbidden();
    });
});

describe('edit', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('writers.edit', Writer::factory()->create()))
            ->assertRedirect(route('login'));
    });

    test('admins see the edit page with the full record', function () {
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('writers.edit', $writer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('writers/edit')
                ->where('writer.data.id', $writer->id)
                ->where('writer.data.bank_name', $writer->bank_name)
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('writers.edit', Writer::factory()->create()))
            ->assertForbidden();
    });
});

describe('store', function () {
    test('admins can create a writer with a generated code', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'default_role' => 'CA',
                'ipi_name_number' => '00123456789',
                'publisher_id' => $publisher->id,
                'controlled_territories' => 'my, sg',
                'rights_types_controlled' => ['performance', 'sync'],
                'taja_percentage_deal' => '70',
                'writer_percentage_deal' => '30',
                'auto_renewal' => '1',
                'pre_term' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $writer = Writer::firstOrFail();

        expect($writer->writer_code)->toBe('WRT-000001')
            ->and($writer->name)->toBe('Fake Writer')
            ->and($writer->status)->toBe(MasterStatus::Active)
            ->and($writer->default_role)->toBe(PartyRole::CA)
            ->and($writer->publisher_id)->toBe($publisher->id)
            ->and($writer->controlled_territories)->toBe(['MY', 'SG'])
            ->and($writer->rights_types_controlled->map->value->all())->toBe(['performance', 'sync'])
            ->and($writer->auto_renewal)->toBeTrue()
            ->and($writer->pre_term)->toBeFalse()
            ->and($writer->taja_percentage_deal)->toBe('70.00');
    });

    test('redirects to the detail page of the new writer', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload())
            ->assertRedirect(route('writers.show', Writer::firstOrFail()));
    });

    test('optional fields can be left empty', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'default_role' => '',
                'ipi_name_number' => '',
                'publisher_id' => '',
                'controlled_territories' => '',
            ]))
            ->assertSessionHasNoErrors();

        $writer = Writer::firstOrFail();

        expect($writer->default_role)->toBeNull()
            ->and($writer->ipi_name_number)->toBeNull()
            ->and($writer->publisher_id)->toBeNull()
            ->and($writer->controlled_territories)->toBeNull()
            ->and($writer->rights_types_controlled)->toBeNull();
    });

    test('requires the mandatory fields', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), [])
            ->assertSessionHasErrors(['name', 'cmo_society', 'status', 'agreement_start_date']);
    });

    test('rejects bad formats', function (string $field, mixed $value, string $errorKey) {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([$field => $value]))
            ->assertSessionHasErrors($errorKey);
    })->with([
        'publisher role as default role' => ['default_role', 'E', 'default_role'],
        'unknown role' => ['default_role', 'XX', 'default_role'],
        'ipi number too short' => ['ipi_name_number', '123', 'ipi_name_number'],
        'unknown rights type' => ['rights_types_controlled', ['streaming'], 'rights_types_controlled.0'],
        'territory with three letters' => ['controlled_territories', 'MYS', 'controlled_territories'],
        'territory that is not a country' => ['controlled_territories', ['MY', 'ZZ'], 'controlled_territories'],
        'unknown status' => ['status', 'archived', 'status'],
        'wrong date format' => ['agreement_start_date', '01/02/2026', 'agreement_start_date'],
        'bad email' => ['email', 'not-an-email', 'email'],
        'percent above 100' => ['taja_percentage_deal', '120', 'taja_percentage_deal'],
        'unknown publisher' => ['publisher_id', 999, 'publisher_id'],
        'phone without a country code' => ['phone', '+12', 'phone'],
        'phone with letters' => ['phone', 'call me', 'phone'],
        'heir mobile number with letters' => ['heir_mobile_number', 'abc', 'heir_mobile_number'],
    ]);

    test('saves nric and phone numbers without dashes', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'nric' => '900101-01-1234',
                'heir_nric' => 'A-123-456',
                'phone' => '+6011-000 0003',
                'heir_mobile_number' => '011-000 0004',
            ]))
            ->assertSessionHasNoErrors();

        $writer = Writer::query()->firstOrFail();

        expect($writer->getRawOriginal('nric'))->toBe('900101011234')
            ->and($writer->getRawOriginal('heir_nric'))->toBe('A123456')
            ->and($writer->getRawOriginal('phone'))->toBe('+60110000003')
            ->and($writer->getRawOriginal('heir_mobile_number'))->toBe('+60110000004');
    });

    test('rejects a deleted publisher', function () {
        $publisher = Publisher::factory()->create();
        $publisher->delete();

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload(['publisher_id' => $publisher->id]))
            ->assertSessionHasErrors('publisher_id');
    });

    test('rejects dates in the wrong order', function (string $field, string $value, string $errorKey) {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'date_of_birth' => '1980-01-01',
                $field => $value,
            ]))
            ->assertSessionHasErrors($errorKey);
    })->with([
        'agreement end before start' => ['agreement_end_date', '2025-12-31', 'agreement_end_date'],
        'death before birth' => ['date_of_death', '1970-01-01', 'date_of_death'],
    ]);

    test('rejects a duplicate ipi name number', function () {
        Writer::factory()->create(['ipi_name_number' => '00123456789']);

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload(['ipi_name_number' => '00123456789']))
            ->assertSessionHasErrors('ipi_name_number');
    });

    test('keeps the ipi number of a deleted writer reserved', function () {
        Writer::factory()->create(['ipi_name_number' => '00123456789'])->delete();

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload(['ipi_name_number' => '00123456789']))
            ->assertSessionHasErrors('ipi_name_number');
    });

    test('accepts a duplicate nric', function () {
        Writer::factory()->create(['nric' => '900101-01-1234']);

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload(['nric' => '900101-01-1234']))
            ->assertSessionHasNoErrors();

        expect(Writer::count())->toBe(2);
    });

    test('the two deals must total 100 when both are given', function (?string $taja, ?string $writer, bool $valid) {
        $response = $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'taja_percentage_deal' => $taja,
                'writer_percentage_deal' => $writer,
            ]));

        $valid
            ? $response->assertSessionHasNoErrors()
            : $response->assertSessionHasErrors('writer_percentage_deal');
    })->with([
        'totals 100' => ['70', '30', true],
        'totals 100 with decimals' => ['33.33', '66.67', true],
        'does not total 100' => ['70', '40', false],
        'only one given' => ['70', null, true],
        'neither given' => [null, null, true],
    ]);

    test('pseudonyms are saved in the order they are given', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'aliases' => [
                    ['name' => 'First Alias', 'ipi_base_number' => '11111111111'],
                    ['name' => 'Second Alias', 'ipi_base_number' => ''],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $aliases = Writer::firstOrFail()->aliases;

        expect($aliases->pluck('name')->all())->toBe(['First Alias', 'Second Alias'])
            ->and($aliases->pluck('position')->all())->toBe([1, 2])
            ->and($aliases[0]->ipi_base_number)->toBe('11111111111')
            ->and($aliases[1]->ipi_base_number)->toBeNull();
    });

    test('blank pseudonym rows are ignored', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'aliases' => [
                    ['name' => '', 'ipi_base_number' => ''],
                    ['name' => 'Only Alias', 'ipi_base_number' => ''],
                ],
            ]))
            ->assertSessionHasNoErrors();

        expect(Writer::firstOrFail()->aliases->pluck('name')->all())->toBe(['Only Alias']);
    });

    test('allows at most four pseudonyms', function () {
        $aliases = array_map(fn (int $number): array => ['name' => "Alias {$number}"], range(1, 5));

        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload(['aliases' => $aliases]))
            ->assertSessionHasErrors('aliases');

        expect(Writer::count())->toBe(0);
    });

    test('a pseudonym needs a name when it has an ipi number', function () {
        $this->actingAs($this->admin)
            ->post(route('writers.store'), writerPayload([
                'aliases' => [['name' => '', 'ipi_base_number' => '11111111111']],
            ]))
            ->assertSessionHasErrors('aliases.0.name');
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('writers.store'), writerPayload())
            ->assertForbidden();

        expect(Writer::count())->toBe(0);
    });
});

describe('update', function () {
    test('redirects to the detail page of the writer', function () {
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload())
            ->assertRedirect(route('writers.show', $writer));
    });

    test('admins can update a writer and keep its code', function () {
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload([
                'name' => 'Renamed Writer',
                'status' => 'inactive',
                'writer_code' => 'WRT-999999',
            ]))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->name)->toBe('Renamed Writer')
            ->and($writer->status)->toBe(MasterStatus::Inactive)
            ->and($writer->writer_code)->toBe('WRT-000001');
    });

    test('updating normalizes nric and phone numbers and an empty value clears them', function () {
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload([
                'nric' => '900202-02-8888',
                'phone' => '+60 11-000 0005',
                'heir_nric' => '',
                'heir_mobile_number' => '',
            ]))
            ->assertSessionHasNoErrors();

        $writer->refresh();

        expect($writer->nric)->toBe('900202028888')
            ->and($writer->phone)->toBe('+60110000005')
            ->and($writer->heir_nric)->toBeNull()
            ->and($writer->heir_mobile_number)->toBeNull();
    });

    test('the publisher link can be changed and cleared', function () {
        $publisher = Publisher::factory()->create();
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['publisher_id' => $publisher->id]))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->publisher_id)->toBe($publisher->id);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['publisher_id' => '']))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->publisher_id)->toBeNull();
    });

    test('territories can be picked as a list, set to worldwide and cleared', function () {
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['controlled_territories' => ['MY', 'BN']]))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->controlled_territories)->toBe(['MY', 'BN']);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['controlled_territories' => ['WW']]))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->controlled_territories)->toBe(['WW']);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['controlled_territories' => '']))
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->controlled_territories)->toBeNull();
    });

    test('rights types can be cleared', function () {
        $writer = Writer::factory()->create(['rights_types_controlled' => ['performance']]);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload())
            ->assertSessionHasNoErrors();

        expect($writer->refresh()->rights_types_controlled)->toBeNull();
    });

    test('pseudonyms are replaced', function () {
        $writer = Writer::factory()->withAliases(3)->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload([
                'aliases' => [['name' => 'New Alias', 'ipi_base_number' => '']],
            ]))
            ->assertSessionHasNoErrors();

        expect($writer->aliases()->pluck('name')->all())->toBe(['New Alias']);
    });

    test('leaving out the pseudonyms removes them', function () {
        $writer = Writer::factory()->withAliases(2)->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload())
            ->assertSessionHasNoErrors();

        expect($writer->aliases()->count())->toBe(0);
    });

    test('keeps the pseudonyms when the update is invalid', function () {
        $writer = Writer::factory()->withAliases(2)->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['name' => '', 'aliases' => [['name' => 'Lost']]]))
            ->assertSessionHasErrors('name');

        expect($writer->aliases()->pluck('name')->contains('Lost'))->toBeFalse()
            ->and($writer->aliases()->count())->toBe(2);
    });

    test('a writer can keep its own ipi number', function () {
        $writer = Writer::factory()->create(['ipi_name_number' => '00123456789']);

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['ipi_name_number' => '00123456789']))
            ->assertSessionHasNoErrors();
    });

    test('rejects the ipi number of another writer', function () {
        Writer::factory()->create(['ipi_name_number' => '00123456789']);
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('writers.update', $writer), writerPayload(['ipi_name_number' => '00123456789']))
            ->assertSessionHasErrors('ipi_name_number');
    });

    test('regular users are forbidden', function () {
        $writer = Writer::factory()->create(['name' => 'Original']);

        $this->actingAs(User::factory()->create())
            ->put(route('writers.update', $writer), writerPayload(['name' => 'Changed']))
            ->assertForbidden();

        expect($writer->refresh()->name)->toBe('Original');
    });
});

describe('destroy', function () {
    test('admins can delete a writer without losing the row', function () {
        $writer = Writer::factory()->withAliases(1)->create();

        $this->actingAs($this->admin)
            ->delete(route('writers.destroy', $writer))
            ->assertRedirect(route('writers.index'));

        expect(Writer::find($writer->id))->toBeNull()
            ->and(Writer::withTrashed()->find($writer->id))->not->toBeNull()
            ->and($writer->aliases()->count())->toBe(1);
    });

    test('a writer that is still a party of a work cannot be deleted', function () {
        $writer = Writer::factory()->create();
        WorkParty::factory()->forWriter($writer)->create(['work_id' => Work::factory()]);

        $this->actingAs($this->admin)
            ->delete(route('writers.destroy', $writer))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        expect(Writer::find($writer->id))->not->toBeNull();
    });

    test('a writer can be deleted once removed from every work, and the party rows are kept', function () {
        $writer = Writer::factory()->create();
        $party = WorkParty::factory()->forWriter($writer)->ended()->create(['work_id' => Work::factory()]);

        $this->actingAs($this->admin)
            ->delete(route('writers.destroy', $writer))
            ->assertRedirect(route('writers.index'));

        expect(Writer::find($writer->id))->toBeNull()
            ->and(WorkParty::find($party->id))->not->toBeNull();
    });

    test('a writer on a deleted work can be deleted', function () {
        $writer = Writer::factory()->create();
        $work = Work::factory()->create();
        WorkParty::factory()->forWriter($writer)->create(['work_id' => $work]);
        $work->delete();

        $this->actingAs($this->admin)
            ->delete(route('writers.destroy', $writer))
            ->assertRedirect(route('writers.index'));

        expect(Writer::find($writer->id))->toBeNull();
    });

    test('regular users are forbidden', function () {
        $writer = Writer::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('writers.destroy', $writer))
            ->assertForbidden();

        expect(Writer::find($writer->id))->not->toBeNull();
    });
});
