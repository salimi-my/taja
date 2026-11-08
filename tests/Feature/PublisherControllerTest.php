<?php

use App\Enums\MasterStatus;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Database\Seeders\CountrySeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid set of form values for creating a publisher.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function publisherPayload(array $overrides = []): array
{
    return [
        'name' => 'Fake Tunes Sdn Bhd',
        'rights_types_granted' => ['performance', 'mechanical'],
        'ipi_name_number' => '00123456789',
        'company_registration_number' => '202001000001',
        'registration_address' => '1 Fake Street, Kuala Lumpur',
        'official_email' => 'office@faketunes.test',
        'society_cmo' => 'MACP',
        'status' => 'active',
        'agreement_start_date' => '2026-01-01',
        'territories_granted' => 'my, sg',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seed(CountrySeeder::class);
});

describe('index', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('publishers.index'))->assertRedirect(route('login'));
    });

    test('regular users can view the list', function () {
        Publisher::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('publishers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishers/index')
                ->has('publishers.data', 3)
            );
    });

    test('shows the first page newest first with the option lists', function () {
        Publisher::factory()->count(12)->create(['society_cmo' => 'MACP']);

        $this->actingAs($this->admin)
            ->get(route('publishers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data', 10)
                ->where('publishers.meta.total', 12)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->where('societies', ['MACP'])
                ->has('rightsTypes', 5)
                ->has('statuses', 2)
                ->has('countries', 249)
            );
    });

    test('does not send private fields in the list', function () {
        Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data.0', fn (Assert $row) => $row
                    ->missing('bank_name')
                    ->missing('client_account_number')
                    ->missing('person_in_charge_nric')
                    ->etc()
                )
            );
    });

    test('publishers can be searched', function (string $column, string $term) {
        Publisher::factory()->create([
            'name' => 'Needle Music',
            'ipi_name_number' => '11111111111',
            'ipi_base_number' => 'I-222222222-2',
            'company_registration_number' => 'CR-333',
        ]);
        Publisher::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data', 1)
                ->where('publishers.data.0.name', 'Needle Music')
            );
    })->with([
        'name' => ['name', 'needle'],
        'ipi name number' => ['ipi_name_number', '1111111'],
        'ipi base number' => ['ipi_base_number', 'I-222222222'],
        'company registration number' => ['company_registration_number', 'CR-333'],
    ]);

    test('publishers can be searched by their code', function () {
        $publisher = Publisher::factory()->create();
        Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['search' => $publisher->publisher_code]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data', 1)
                ->where('publishers.data.0.publisher_code', 'PUB-000001')
            );
    });

    test('publishers can be filtered by status', function () {
        Publisher::factory()->count(2)->create();
        Publisher::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['status' => [MasterStatus::Inactive->value]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data', 1)
                ->where('publishers.data.0.status', 'inactive')
            );
    });

    test('publishers can be filtered by society', function () {
        Publisher::factory()->create(['society_cmo' => 'MACP']);
        Publisher::factory()->create(['society_cmo' => 'WAMI']);
        Publisher::factory()->create(['society_cmo' => 'Non-Member']);

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['societies' => ['MACP', 'WAMI']]))
            ->assertInertia(fn (Assert $page) => $page->has('publishers.data', 2));
    });

    test('publishers can be filtered by rights type', function () {
        Publisher::factory()->create(['rights_types_granted' => ['performance']]);
        Publisher::factory()->create(['rights_types_granted' => ['performance', 'sync']]);
        Publisher::factory()->create(['rights_types_granted' => ['lyrics']]);

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['rights_types' => ['sync']]))
            ->assertInertia(fn (Assert $page) => $page->has('publishers.data', 1));

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['rights_types' => ['sync', 'lyrics']]))
            ->assertInertia(fn (Assert $page) => $page->has('publishers.data', 2));
    });

    test('publishers can be filtered by territory and worldwide publishers always match', function () {
        Publisher::factory()->create(['territories_granted' => ['MY', 'SG']]);
        Publisher::factory()->create(['territories_granted' => ['BN']]);
        Publisher::factory()->create(['territories_granted' => ['WW']]);

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['territories' => ['sg']]))
            ->assertInertia(fn (Assert $page) => $page->has('publishers.data', 2));
    });

    test('filters can be combined', function () {
        Publisher::factory()->create(['society_cmo' => 'MACP']);
        Publisher::factory()->inactive()->create(['society_cmo' => 'MACP']);
        Publisher::factory()->create(['society_cmo' => 'WAMI']);

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['societies' => ['MACP'], 'status' => ['active']]))
            ->assertInertia(fn (Assert $page) => $page->has('publishers.data', 1));
    });

    test('publishers can be sorted by a column', function () {
        Publisher::factory()->create(['name' => 'Zed Music']);
        Publisher::factory()->create(['name' => 'Aaron Music']);

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page->where('publishers.data.0.name', 'Aaron Music'));

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertInertia(fn (Assert $page) => $page->where('publishers.data.0.name', 'Zed Music'));
    });

    test('every column shown in the table can be sorted', function (string $column) {
        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['sort' => $column]))
            ->assertOk()
            ->assertSessionHasNoErrors();
    })->with(['name', 'publisher_code', 'society_cmo', 'status', 'agreement_start_date', 'created_at']);

    test('rejects a column that cannot be sorted', function () {
        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['sort' => 'bank_name']))
            ->assertSessionHasErrors('sort');
    });

    test('the page size and page number are respected', function () {
        Publisher::factory()->count(27)->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.index', ['per_page' => 25, 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('publishers.data', 2)
                ->where('publishers.meta.current_page', 2)
            );
    });
});

describe('show', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('publishers.show', Publisher::factory()->create()))
            ->assertRedirect(route('login'));
    });

    test('regular users can see every field of a publisher', function () {
        $publisher = Publisher::factory()->create(['bank_name' => 'Fake Bank']);

        $this->actingAs(User::factory()->create())
            ->get(route('publishers.show', $publisher))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishers/show')
                ->where('publisher.data.publisher_code', 'PUB-000001')
                ->where('publisher.data.bank_name', 'Fake Bank')
                ->where('publisher.data.company_registration_number', $publisher->company_registration_number)
            );
    });

    test('lists the works the publisher is a party of, with a toggle for ended ones', function () {
        $publisher = Publisher::factory()->create();
        WorkParty::factory()->forPublisher($publisher)->create(['work_id' => Work::factory()]);
        WorkParty::factory()->forPublisher($publisher)->ended()->create(['work_id' => Work::factory()]);

        $this->actingAs(User::factory()->create())
            ->get(route('publishers.show', $publisher))
            ->assertInertia(fn (Assert $page) => $page
                ->where('includeEnded', false)
                ->has('catalogue.data', 1)
                ->where('catalogue.data.0.party_type', 'publisher')
            );

        $this->actingAs($this->admin)
            ->get(route('publishers.show', ['publisher' => $publisher, 'include_ended' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('catalogue.data', 2));
    });

    test('a deleted publisher is not found', function () {
        $publisher = Publisher::factory()->create();
        $publisher->delete();

        $this->actingAs($this->admin)
            ->get(route('publishers.show', $publisher))
            ->assertNotFound();
    });
});

describe('create', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('publishers.create'))->assertRedirect(route('login'));
    });

    test('admins see the create page with the option lists', function () {
        $this->actingAs($this->admin)
            ->get(route('publishers.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishers/create')
                ->has('rightsTypes', 5)
                ->has('statuses', 2)
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('publishers.create'))
            ->assertForbidden();
    });
});

describe('edit', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('publishers.edit', Publisher::factory()->create()))
            ->assertRedirect(route('login'));
    });

    test('admins see the edit page with the full record', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('publishers.edit', $publisher))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishers/edit')
                ->where('publisher.data.id', $publisher->id)
                ->where('publisher.data.bank_name', $publisher->bank_name)
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('publishers.edit', Publisher::factory()->create()))
            ->assertForbidden();
    });
});

describe('store', function () {
    test('admins can create a publisher with a generated code', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload([
                'taja_percentage_deal' => '70',
                'client_percentage_deal' => '30',
                'auto_renewal' => '1',
                'pre_term' => '0',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $publisher = Publisher::firstOrFail();

        expect($publisher->publisher_code)->toBe('PUB-000001')
            ->and($publisher->name)->toBe('Fake Tunes Sdn Bhd')
            ->and($publisher->status)->toBe(MasterStatus::Active)
            ->and($publisher->rights_types_granted->map->value->all())->toBe(['performance', 'mechanical'])
            ->and($publisher->territories_granted)->toBe(['MY', 'SG'])
            ->and($publisher->auto_renewal)->toBeTrue()
            ->and($publisher->pre_term)->toBeFalse()
            ->and($publisher->taja_percentage_deal)->toBe('70.00');
    });

    test('redirects to the detail page of the new publisher', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload())
            ->assertRedirect(route('publishers.show', Publisher::firstOrFail()));
    });

    test('optional fields can be left empty', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload(['ipi_name_number' => '']))
            ->assertSessionHasNoErrors();

        expect(Publisher::firstOrFail()->ipi_name_number)->toBeNull();
    });

    test('requires the mandatory fields', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), [])
            ->assertSessionHasErrors([
                'name',
                'rights_types_granted',
                'company_registration_number',
                'registration_address',
                'official_email',
                'society_cmo',
                'status',
                'agreement_start_date',
                'territories_granted',
            ]);
    });

    test('rejects bad formats', function (string $field, mixed $value, string $errorKey) {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload([$field => $value]))
            ->assertSessionHasErrors($errorKey);
    })->with([
        'unknown rights type' => ['rights_types_granted', ['streaming'], 'rights_types_granted.0'],
        'ipi number too short' => ['ipi_name_number', '123', 'ipi_name_number'],
        'territory with three letters' => ['territories_granted', 'MYS', 'territories_granted'],
        'worldwide mixed with a country' => ['territories_granted', 'WW, MY', 'territories_granted'],
        'territory that is not a country' => ['territories_granted', ['MY', 'ZZ'], 'territories_granted'],
        'unknown status' => ['status', 'archived', 'status'],
        'wrong date format' => ['agreement_start_date', '01/02/2026', 'agreement_start_date'],
        'bad email' => ['official_email', 'not-an-email', 'official_email'],
        'percent above 100' => ['taja_percentage_deal', '120', 'taja_percentage_deal'],
        'mobile number without a country code' => ['person_in_charge_mobile_no', '+12', 'person_in_charge_mobile_no'],
        'mobile number with letters' => ['person_in_charge_mobile_no', 'abc', 'person_in_charge_mobile_no'],
    ]);

    test('saves the nric and mobile number without dashes', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload([
                'person_in_charge_nric' => '900101-01-9999',
                'person_in_charge_mobile_no' => '+6011-000 0001',
            ]))
            ->assertSessionHasNoErrors();

        $publisher = Publisher::query()->firstOrFail();

        expect($publisher->getRawOriginal('person_in_charge_nric'))->toBe('900101019999')
            ->and($publisher->getRawOriginal('person_in_charge_mobile_no'))->toBe('+60110000001');
    });

    test('a national mobile number starting with 0 is saved as a Malaysian number', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload(['person_in_charge_mobile_no' => '011-000 0002']))
            ->assertSessionHasNoErrors();

        expect(Publisher::query()->firstOrFail()->person_in_charge_mobile_no)->toBe('+60110000002');
    });

    test('rejects an end date before the start date', function () {
        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload(['agreement_end_date' => '2025-12-31']))
            ->assertSessionHasErrors('agreement_end_date');
    });

    test('rejects duplicate company registration and ipi numbers', function () {
        Publisher::factory()->create([
            'company_registration_number' => '202001000001',
            'ipi_name_number' => '00123456789',
        ]);

        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload())
            ->assertSessionHasErrors(['company_registration_number', 'ipi_name_number']);
    });

    test('keeps the unique values of a deleted publisher reserved', function () {
        Publisher::factory()->create(['company_registration_number' => '202001000001'])->delete();

        $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload())
            ->assertSessionHasErrors('company_registration_number');
    });

    test('the two deals must total 100 when both are given', function (?string $taja, ?string $client, bool $valid) {
        $response = $this->actingAs($this->admin)
            ->post(route('publishers.store'), publisherPayload([
                'taja_percentage_deal' => $taja,
                'client_percentage_deal' => $client,
            ]));

        $valid
            ? $response->assertSessionHasNoErrors()
            : $response->assertSessionHasErrors('client_percentage_deal');
    })->with([
        'totals 100' => ['70', '30', true],
        'totals 100 with decimals' => ['33.33', '66.67', true],
        'does not total 100' => ['70', '40', false],
        'only one given' => ['70', null, true],
        'neither given' => [null, null, true],
    ]);

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('publishers.store'), publisherPayload())
            ->assertForbidden();

        expect(Publisher::count())->toBe(0);
    });
});

describe('update', function () {
    test('redirects to the detail page of the publisher', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload())
            ->assertRedirect(route('publishers.show', $publisher));
    });

    test('territories can be picked as a list and set to worldwide', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload(['territories_granted' => ['MY', 'BN']]))
            ->assertSessionHasNoErrors();

        expect($publisher->refresh()->territories_granted)->toBe(['MY', 'BN']);

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload(['territories_granted' => ['WW']]))
            ->assertSessionHasNoErrors();

        expect($publisher->refresh()->territories_granted)->toBe(['WW']);
    });

    test('territories cannot be cleared', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload(['territories_granted' => '']))
            ->assertSessionHasErrors('territories_granted');
    });

    test('admins can update a publisher and keep its code', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload([
                'name' => 'Renamed Music',
                'status' => 'inactive',
                'publisher_code' => 'PUB-999999',
            ]))
            ->assertSessionHasNoErrors();

        expect($publisher->refresh()->name)->toBe('Renamed Music')
            ->and($publisher->status)->toBe(MasterStatus::Inactive)
            ->and($publisher->publisher_code)->toBe('PUB-000001');
    });

    test('a publisher can keep its own unique values', function () {
        $publisher = Publisher::factory()->create([
            'company_registration_number' => '202001000001',
            'ipi_name_number' => '00123456789',
        ]);

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload())
            ->assertSessionHasNoErrors();
    });

    test('rejects the unique values of another publisher', function () {
        Publisher::factory()->create(['company_registration_number' => '202001000001']);
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('publishers.update', $publisher), publisherPayload())
            ->assertSessionHasErrors('company_registration_number');
    });

    test('regular users are forbidden', function () {
        $publisher = Publisher::factory()->create(['name' => 'Original']);

        $this->actingAs(User::factory()->create())
            ->put(route('publishers.update', $publisher), publisherPayload(['name' => 'Changed']))
            ->assertForbidden();

        expect($publisher->refresh()->name)->toBe('Original');
    });
});

describe('destroy', function () {
    test('admins can delete a publisher without losing the row', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('publishers.destroy', $publisher))
            ->assertRedirect(route('publishers.index'));

        expect(Publisher::find($publisher->id))->toBeNull()
            ->and(Publisher::withTrashed()->find($publisher->id))->not->toBeNull();
    });

    test('a publisher that still has writers cannot be deleted', function () {
        $publisher = Publisher::factory()->create();
        Writer::factory()->withPublisher($publisher)->create();

        $this->actingAs($this->admin)
            ->delete(route('publishers.destroy', $publisher))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        expect(Publisher::find($publisher->id))->not->toBeNull();
    });

    test('a publisher that is a party of a work cannot be deleted', function () {
        $publisher = Publisher::factory()->create();
        WorkParty::factory()->forPublisher($publisher)->create(['work_id' => Work::factory()]);

        $this->actingAs($this->admin)
            ->delete(route('publishers.destroy', $publisher))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        expect(Publisher::find($publisher->id))->not->toBeNull();
    });

    test('a publisher can be deleted once its writers are deleted', function () {
        $publisher = Publisher::factory()->create();
        Writer::factory()->withPublisher($publisher)->create()->delete();

        $this->actingAs($this->admin)
            ->delete(route('publishers.destroy', $publisher))
            ->assertRedirect(route('publishers.index'));

        expect(Publisher::find($publisher->id))->toBeNull();
    });

    test('regular users are forbidden', function () {
        $publisher = Publisher::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('publishers.destroy', $publisher))
            ->assertForbidden();

        expect(Publisher::find($publisher->id))->not->toBeNull();
    });
});
