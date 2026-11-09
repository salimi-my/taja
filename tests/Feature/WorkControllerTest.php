<?php

use App\Enums\ConflictStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Enums\RightsChangeType;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Database\Seeders\CountrySeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid set of form values for creating a work, without parties.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function workPayload(array $overrides = []): array
{
    return [
        'macp_work_id' => 'MACP-0001',
        'english_title' => 'Fake Song',
        'registration_status' => 'draft',
        'is_public_domain' => '0',
        'conflict_status' => 'no_conflict',
        ...$overrides,
    ];
}

/**
 * A valid party row for the parties repeater.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function partyPayload(array $overrides = []): array
{
    return [
        'role' => 'C',
        'performance_share' => '50',
        'mechanical_share' => '50',
        'is_controlled' => '1',
        'territories_controlled' => ['WW'],
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seed(CountrySeeder::class);
});

describe('index', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('works.index'))->assertRedirect(route('login'));
    });

    test('regular users can view the list', function () {
        Work::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('works.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('works/index')
                ->has('works.data', 3)
            );
    });

    test('shows the first page newest first with the option lists', function () {
        Work::factory()->count(12)->create(['language' => 'Malay', 'genre' => 'Pop', 'work_type' => 'ORG']);

        $this->actingAs($this->admin)
            ->get(route('works.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('works.data', 10)
                ->where('works.meta.total', 12)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->where('languages', ['Malay'])
                ->where('genres', ['Pop'])
                ->has('registrationStatuses', 7)
                ->has('conflictStatuses', 5)
            );
    });

    test('shows the current contributors of each work', function () {
        $work = Work::factory()->withParties()->create();
        WorkParty::factory()->ended()->create(['work_id' => $work->id, 'contributor_name' => 'Former Writer']);

        $this->actingAs($this->admin)
            ->get(route('works.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('works.data.0.parties_count', 3)
                ->where('works.data.0.contributors', fn ($contributors) => collect($contributors)->pluck('name')->doesntContain('Former Writer'))
            );
    });

    test('searches by every identifier, title and contributor', function (string $term) {
        $work = Work::factory()->create([
            'macp_work_id' => 'MACP-FINDME',
            'english_title' => 'Sunrise Melody',
            'other_title' => 'Lagu Pagi',
            'iswc' => 'T9876543210',
        ]);
        $work->isrcs()->create(['isrc' => 'MYZZZ2600099']);
        $writer = Writer::factory()->create(['name' => 'Zainal Composer']);
        $publisher = Publisher::factory()->create(['name' => 'Bintang Publishing']);
        WorkParty::factory()->forWriter($writer)->create(['work_id' => $work->id]);
        WorkParty::factory()->forPublisher($publisher)->create(['work_id' => $work->id]);
        Work::factory()->count(2)->create();

        $term = str_replace('{tap}', $work->tap_number, $term);

        $this->actingAs($this->admin)
            ->get(route('works.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('works.data', 1)
                ->where('works.data.0.id', $work->id)
            );
    })->with([
        'english title' => 'Sunrise',
        'other title' => 'Lagu Pagi',
        'tap number' => '{tap}',
        'macp work id' => 'FINDME',
        'iswc' => 'T9876543210',
        'isrc' => 'MYZZZ2600099',
        'contributor name' => 'Zainal',
        'publisher name' => 'Bintang',
    ]);

    test('search does not match a superseded contributor', function () {
        $work = Work::factory()->create();
        WorkParty::factory()->superseded()->create(['work_id' => $work->id, 'contributor_name' => 'Mistaken Entry']);

        $this->actingAs($this->admin)
            ->get(route('works.index', ['search' => 'Mistaken']))
            ->assertInertia(fn (Assert $page) => $page->has('works.data', 0));
    });

    test('filters by registration status, conflict status, work type, language and genre', function (array $query, string $expected) {
        Work::factory()->create([
            'english_title' => 'Match',
            'registration_status' => RegistrationStatus::Accepted,
            'conflict_status' => ConflictStatus::ShareConflict,
            'work_type' => 'ORG',
            'language' => 'Malay',
            'genre' => 'Pop',
        ]);
        Work::factory()->create([
            'english_title' => 'Other',
            'registration_status' => RegistrationStatus::Draft,
            'conflict_status' => ConflictStatus::NoConflict,
            'work_type' => 'ARR',
            'language' => 'English',
            'genre' => 'Rock',
        ]);

        $this->actingAs($this->admin)
            ->get(route('works.index', $query))
            ->assertInertia(fn (Assert $page) => $page
                ->has('works.data', 1)
                ->where('works.data.0.title', $expected)
            );
    })->with([
        'registration' => [['registration_statuses' => ['accepted']], 'Match'],
        'conflict' => [['conflict_statuses' => ['share_conflict']], 'Match'],
        'language' => [['languages' => ['English']], 'Other'],
        'genre' => [['genres' => ['Pop']], 'Match'],
    ]);

    test('filters by registration date range', function () {
        Work::factory()->create(['english_title' => 'Early', 'registration_date' => '2026-01-10']);
        Work::factory()->create(['english_title' => 'Middle', 'registration_date' => '2026-03-10']);
        Work::factory()->create(['english_title' => 'Late', 'registration_date' => '2026-06-10']);

        $this->actingAs($this->admin)
            ->get(route('works.index', ['start_date' => '2026-02-01', 'end_date' => '2026-04-30']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('works.data', 1)
                ->where('works.data.0.title', 'Middle')
            );
    });

    test('sorts by a whitelisted column and rejects others', function () {
        Work::factory()->create(['macp_work_id' => 'MACP-B']);
        Work::factory()->create(['macp_work_id' => 'MACP-A']);

        $this->actingAs($this->admin)
            ->get(route('works.index', ['sort' => 'macp_work_id', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page->where('works.data.0.macp_work_id', 'MACP-A'));

        $this->actingAs($this->admin)
            ->get(route('works.index', ['sort' => 'deleted_at']))
            ->assertSessionHasErrors('sort');
    });

    test('paginates', function () {
        Work::factory()->count(12)->create();

        $this->actingAs($this->admin)
            ->get(route('works.index', ['page' => 2, 'per_page' => 10]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('works.data', 2)
                ->where('works.meta.current_page', 2)
            );
    });

    test('does not list deleted works', function () {
        Work::factory()->create()->delete();

        $this->actingAs($this->admin)
            ->get(route('works.index'))
            ->assertInertia(fn (Assert $page) => $page->has('works.data', 0));
    });
});

describe('show', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('works.show', Work::factory()->create()))->assertRedirect(route('login'));
    });

    test('regular users see the work, its ISRCs and its current parties', function () {
        $work = Work::factory()->withIsrcs(2)->withParties()->create();
        WorkParty::factory()->ended()->create(['work_id' => $work->id]);
        WorkParty::factory()->superseded()->create(['work_id' => $work->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('works.show', $work))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('works/show')
                ->where('work.data.tap_number', 'TAP-000001')
                ->where('work.data.macp_work_id', $work->macp_work_id)
                ->has('work.data.isrcs', 2)
                ->has('parties.data', 3)
                ->where('parties.data.0.party_type', 'writer')
            );
    });

    test('shows the parties in effect on the as_of date', function () {
        $work = Work::factory()->create();
        WorkParty::factory()->forWriter()->create(['work_id' => $work->id, 'effective_start_date' => '2024-01-01', 'effective_end_date' => '2024-12-31']);
        WorkParty::factory()->forWriter()->create(['work_id' => $work->id, 'effective_start_date' => '2025-01-01']);
        WorkParty::factory()->forWriter()->superseded()->create(['work_id' => $work->id, 'effective_start_date' => '2024-01-01']);

        $this->actingAs($this->admin)
            ->get(route('works.show', ['work' => $work, 'as_of' => '2024-06-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('asOf', '2024-06-01')
                ->has('parties.data', 1)
                ->where('parties.data.0.effective_start_date', '2024-01-01')
            );

        $this->actingAs($this->admin)
            ->get(route('works.show', ['work' => $work, 'as_of' => '2025-06-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('parties.data', 1)
                ->where('parties.data.0.effective_start_date', '2025-01-01')
            );
    });

    test('rejects an as_of date in the wrong format', function () {
        $this->actingAs($this->admin)
            ->get(route('works.show', ['work' => Work::factory()->create(), 'as_of' => '01/06/2024']))
            ->assertSessionHasErrors('as_of');
    });

    test('sends the party options to admins only', function () {
        $work = Work::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('works.show', $work))
            ->assertInertia(fn (Assert $page) => $page->has('partyOptions.roles')->has('partyOptions.countries'));

        $this->actingAs(User::factory()->create())
            ->get(route('works.show', $work))
            ->assertInertia(fn (Assert $page) => $page->where('partyOptions', null));
    });

    test('loads the history newest first, 15 entries a page', function () {
        $work = Work::factory()->create();
        RightsHistory::factory()->count(16)->create(['work_id' => $work->id, 'effective_date' => '2024-01-01']);
        $newest = RightsHistory::factory()->create(['work_id' => $work->id, 'effective_date' => '2025-01-01']);
        RightsHistory::factory()->create(['effective_date' => '2026-01-01']);

        $this->actingAs(User::factory()->create())
            ->get(route('works.show', $work))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('history')
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->has('history.data', 15)
                    ->where('history.data.0.id', $newest->id)
                    ->where('history.meta.total', 17)
                )
            );
    });

    test('a deleted work is not found', function () {
        $work = Work::factory()->create();
        $work->delete();

        $this->actingAs($this->admin)->get(route('works.show', $work))->assertNotFound();
    });

    test('still shows a writer that was deleted', function () {
        $work = Work::factory()->create();
        $writer = Writer::factory()->create();
        WorkParty::factory()->forWriter($writer)->create(['work_id' => $work->id]);
        $writer->delete();

        $this->actingAs($this->admin)
            ->get(route('works.show', $work))
            ->assertInertia(fn (Assert $page) => $page->where('parties.data.0.party_id', $writer->id));
    });
});

describe('create', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('works.create'))->assertRedirect(route('login'));
    });

    test('admins see the create page with the option lists', function () {
        $writer = Writer::factory()->create(['default_role' => PartyRole::CA]);
        Publisher::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('works.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('works/create')
                ->has('roles', 7)
                ->has('writers', 1)
                ->where('writers.0.id', $writer->id)
                ->where('writers.0.default_role', 'CA')
                ->has('publishers', 1)
                ->has('countries')
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())->get(route('works.create'))->assertForbidden();
    });
});

describe('store', function () {
    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('works.store'), workPayload())
            ->assertForbidden();

        expect(Work::count())->toBe(0);
    });

    test('creates a work with a generated TAP number and redirects to it', function () {
        $response = $this->actingAs($this->admin)->post(route('works.store'), workPayload());

        $work = Work::firstOrFail();

        $response->assertRedirect(route('works.show', $work))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        expect($work->tap_number)->toBe('TAP-000001')
            ->and($work->macp_work_id)->toBe('MACP-0001')
            ->and($work->registration_status)->toBe(RegistrationStatus::Draft)
            ->and($work->is_public_domain)->toBeFalse();
    });

    test('never reuses a TAP number after a work is deleted', function () {
        $this->actingAs($this->admin)->post(route('works.store'), workPayload());
        Work::firstOrFail()->delete();

        $this->actingAs($this->admin)->post(route('works.store'), workPayload(['macp_work_id' => 'MACP-0002']));

        expect(Work::pluck('tap_number')->all())->toBe(['TAP-000002'])
            ->and(Work::withTrashed()->pluck('tap_number')->all())->toBe(['TAP-000001', 'TAP-000002']);
    });

    test('ignores a TAP number sent by the client', function () {
        $this->actingAs($this->admin)->post(route('works.store'), workPayload(['tap_number' => 'TAP-999999']));

        expect(Work::firstOrFail()->tap_number)->toBe('TAP-000001');
    });

    test('defaults the conflict status and uppercases the work type', function () {
        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'conflict_status' => '',
            'work_type' => ' org ',
        ]));

        $work = Work::firstOrFail();

        expect($work->conflict_status)->toBe(ConflictStatus::NoConflict)
            ->and($work->work_type)->toBe('ORG');
    });

    test('stores ISRCs uppercased, without hyphens and without repeats', function () {
        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'isrcs' => ['my-abc-26-00001', 'MYABC2600001', '', 'MYABC2600002'],
        ]));

        expect(Work::firstOrFail()->isrcs->pluck('isrc')->all())->toBe(['MYABC2600001', 'MYABC2600002']);
    });

    test('creates the parties with the default effective date and the master name', function () {
        $writer = Writer::factory()->create(['name' => 'Fake Composer', 'agreement_start_date' => '2024-02-02']);
        $publisher = Publisher::factory()->create(['name' => 'Fake Publisher', 'agreement_start_date' => '2024-03-03']);

        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'administration_start_date' => '2025-05-05',
            'parties' => [
                partyPayload(['writer_id' => $writer->id, 'performance_share' => '25', 'mechanical_share' => '25']),
                partyPayload(['role' => 'E', 'publisher_id' => $publisher->id, 'performance_share' => '75', 'mechanical_share' => '75', 'territories_controlled' => ['MY', 'SG'], 'territory_exclusions' => ['SG']]),
            ],
        ]))->assertSessionHasNoErrors();

        $parties = Work::firstOrFail()->parties;

        expect($parties)->toHaveCount(2)
            ->and($parties[0]->writer_id)->toBe($writer->id)
            ->and($parties[0]->contributor_name)->toBe('Fake Composer')
            ->and($parties[0]->role)->toBe(PartyRole::C)
            ->and($parties[0]->effective_start_date->toDateString())->toBe('2025-05-05')
            ->and($parties[0]->effective_end_date)->toBeNull()
            ->and($parties[0]->superseded_at)->toBeNull()
            ->and($parties[1]->publisher_id)->toBe($publisher->id)
            ->and($parties[1]->contributor_name)->toBe('Fake Publisher')
            ->and($parties[1]->territories_controlled)->toBe(['MY', 'SG'])
            ->and($parties[1]->territory_exclusions)->toBe(['SG'])
            ->and($parties[1]->effective_start_date->toDateString())->toBe('2025-05-05');
    });

    test('writes a work-created entry and one party-added entry per party', function () {
        $writer = Writer::factory()->create(['agreement_start_date' => '2024-02-02']);
        $publisher = Publisher::factory()->create();

        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'administration_start_date' => '2025-05-05',
            'parties' => [
                partyPayload(['writer_id' => $writer->id, 'performance_share' => '25', 'mechanical_share' => '25']),
                partyPayload(['role' => 'E', 'publisher_id' => $publisher->id, 'performance_share' => '75', 'mechanical_share' => '75']),
            ],
        ]))->assertSessionHasNoErrors();

        $work = Work::firstOrFail();
        $entries = RightsHistory::query()->where('work_id', $work->id)->orderBy('id')->get();

        expect($entries->pluck('change_type')->all())->toBe([RightsChangeType::WorkCreated, RightsChangeType::PartyAdded, RightsChangeType::PartyAdded])
            ->and($entries->pluck('user_id')->unique()->all())->toBe([$this->admin->id])
            ->and($entries[0]->effective_date->toDateString())->toBe('2025-05-05')
            ->and($entries[0]->new_snapshot['tap_number'])->toBe($work->tap_number)
            ->and($entries[0]->previous_snapshot)->toBeNull()
            ->and($entries[1]->work_party_id)->toBe($work->parties[0]->id)
            ->and($entries[1]->effective_date->toDateString())->toBe('2025-05-05')
            ->and($entries[1]->new_snapshot['performance_share'])->toBe('25.00');
    });

    test('writes no history when the work is invalid', function () {
        $this->actingAs($this->admin)->post(route('works.store'), workPayload(['macp_work_id' => '']));

        expect(RightsHistory::count())->toBe(0);
    });

    test('falls back to the agreement start date of each party', function () {
        $writer = Writer::factory()->create(['agreement_start_date' => '2024-02-02']);
        $publisher = Publisher::factory()->create(['agreement_start_date' => '2024-03-03']);

        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'parties' => [
                partyPayload(['writer_id' => $writer->id, 'performance_share' => '40', 'mechanical_share' => '40']),
                partyPayload(['role' => 'E', 'publisher_id' => $publisher->id, 'performance_share' => '60', 'mechanical_share' => '60']),
            ],
        ]))->assertSessionHasNoErrors();

        $parties = Work::firstOrFail()->parties;

        expect($parties->pluck('effective_start_date')->map->toDateString()->sort()->values()->all())->toBe(['2024-02-02', '2024-03-03']);
    });

    test('keeps a custom name on the work', function () {
        $writer = Writer::factory()->create(['name' => 'Real Name']);

        $this->actingAs($this->admin)->post(route('works.store'), workPayload([
            'parties' => [partyPayload(['writer_id' => $writer->id, 'contributor_name' => 'Stage Name'])],
        ]));

        expect(Work::firstOrFail()->parties->first()->contributor_name)->toBe('Stage Name');
    });

    test('creates a draft work without parties', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload(['parties' => []]))
            ->assertSessionHasNoErrors();

        expect(Work::firstOrFail()->parties)->toHaveCount(0);
    });

    test('drops blank party rows', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload(['parties' => [['role' => 'C', 'is_controlled' => '1']]]))
            ->assertSessionHasNoErrors();

        expect(Work::firstOrFail()->parties)->toHaveCount(0);
    });

    test('rejects a duplicate macp work id, also for a deleted work', function () {
        Work::factory()->create(['macp_work_id' => 'MACP-0001'])->delete();

        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload())
            ->assertSessionHasErrors('macp_work_id');
    });

    test('needs at least one title', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload(['english_title' => '', 'other_title' => '']))
            ->assertSessionHasErrors(['english_title', 'other_title']);

        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload(['english_title' => '', 'other_title' => 'Lagu']))
            ->assertSessionHasNoErrors();
    });

    test('validates the required fields and the formats', function (string $field, mixed $value) {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload([$field => $value]))
            ->assertSessionHasErrors($field);

        expect(Work::count())->toBe(0);
    })->with([
        'missing macp id' => ['macp_work_id', ''],
        'missing registration status' => ['registration_status', ''],
        'unknown registration status' => ['registration_status', 'approved'],
        'missing public domain' => ['is_public_domain', ''],
        'unknown conflict status' => ['conflict_status', 'big_conflict'],
        'bad iswc' => ['iswc', 'T123'],
        'bad duration' => ['duration', '3:20'],
        'bad date' => ['registration_date', '05/11/2026'],
        'bad url' => ['song_link', 'not a url'],
    ]);

    test('rejects an invalid ISRC', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload(['isrcs' => ['SHORT']]))
            ->assertSessionHasErrors('isrcs.0');
    });

    test('rejects administration dates in the wrong order', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload([
                'administration_start_date' => '2026-05-01',
                'administration_end_date' => '2026-04-01',
            ]))
            ->assertSessionHasErrors('administration_end_date');
    });

    test('accepts the valid formats', function () {
        $this->actingAs($this->admin)
            ->post(route('works.store'), workPayload([
                'iswc' => 'T1234567890',
                'duration' => '00:03:25',
                'registration_date' => '2026-05-11',
                'song_link' => 'https://example.com/song',
                'isrcs' => ['MYABC2600001'],
            ]))
            ->assertSessionHasNoErrors();
    });

    describe('parties', function () {
        test('rejects shares that add up to more than 100 for any rights type', function (string $field) {
            $first = Writer::factory()->create();
            $second = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload([
                    'parties' => [
                        partyPayload(['writer_id' => $first->id, $field => '60']),
                        partyPayload(['role' => 'A', 'writer_id' => $second->id, $field => '60']),
                    ],
                ]))
                ->assertSessionHasErrors('parties');

            expect(Work::count())->toBe(0);
        })->with(['performance_share', 'mechanical_share', 'sync_share', 'lyrics_share']);

        test('accepts shares that add up to less than 100', function () {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload([
                    'parties' => [partyPayload(['writer_id' => $writer->id, 'performance_share' => '30', 'mechanical_share' => '30'])],
                ]))
                ->assertSessionHasNoErrors();

            expect(Work::firstOrFail()->parties)->toHaveCount(1);
        });

        test('rejects a duplicate party', function () {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload([
                    'parties' => [
                        partyPayload(['writer_id' => $writer->id, 'performance_share' => '10', 'mechanical_share' => '10']),
                        partyPayload(['writer_id' => $writer->id, 'performance_share' => '10', 'mechanical_share' => '10']),
                    ],
                ]))
                ->assertSessionHasErrors('parties.1.writer_id');

            expect(Work::count())->toBe(0);
        });

        test('allows the same writer as composer and as author', function () {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload([
                    'parties' => [
                        partyPayload(['writer_id' => $writer->id, 'performance_share' => '25', 'mechanical_share' => '25']),
                        partyPayload(['role' => 'A', 'writer_id' => $writer->id, 'performance_share' => '25', 'mechanical_share' => '25']),
                    ],
                ]))
                ->assertSessionHasNoErrors();
        });

        test('a writer role needs a writer and a publisher role needs a publisher', function (array $party, string $errorKey) {
            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload($party)]]))
                ->assertSessionHasErrors($errorKey);
        })->with([
            'writer role without writer' => [['role' => 'C', 'performance_share' => '10'], 'parties.0.writer_id'],
            'publisher role without publisher' => [['role' => 'E', 'performance_share' => '10'], 'parties.0.publisher_id'],
        ]);

        test('a writer role cannot carry a publisher and the other way round', function () {
            $writer = Writer::factory()->create();
            $publisher = Publisher::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['role' => 'C', 'writer_id' => $writer->id, 'publisher_id' => $publisher->id])]]))
                ->assertSessionHasErrors('parties.0.publisher_id');

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['role' => 'SE', 'writer_id' => $writer->id, 'publisher_id' => $publisher->id])]]))
                ->assertSessionHasErrors('parties.0.writer_id');
        });

        test('rejects a deleted or unknown writer', function () {
            $writer = Writer::factory()->create();
            $writer->delete();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['writer_id' => $writer->id])]]))
                ->assertSessionHasErrors('parties.0.writer_id');

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['writer_id' => 9999])]]))
                ->assertSessionHasErrors('parties.0.writer_id');
        });

        test('validates each party field', function (string $field, mixed $value) {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['writer_id' => $writer->id, $field => $value])]]))
                ->assertSessionHasErrors("parties.0.{$field}");
        })->with([
            'unknown role' => ['role', 'X'],
            'missing performance share' => ['performance_share', ''],
            'share above 100' => ['performance_share', '101'],
            'share with three decimals' => ['mechanical_share', '10.123'],
            'negative sync share' => ['sync_share', '-1'],
            'missing controlled flag' => ['is_controlled', ''],
            'missing territories' => ['territories_controlled', ''],
            'unknown territory' => ['territories_controlled', ['ZZ']],
            'worldwide with a country' => ['territories_controlled', ['WW', 'MY']],
        ]);

        test('parses territories typed as text', function () {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload([
                    'writer_id' => $writer->id,
                    'territories_controlled' => 'my, sg',
                ])]]))
                ->assertSessionHasNoErrors();

            expect(Work::firstOrFail()->parties->first()->territories_controlled)->toBe(['MY', 'SG']);
        });

        test('creates nothing when a party is invalid', function () {
            $writer = Writer::factory()->create();

            $this->actingAs($this->admin)
                ->post(route('works.store'), workPayload(['parties' => [partyPayload(['writer_id' => $writer->id, 'performance_share' => '150'])]]));

            expect(Work::count())->toBe(0)
                ->and(WorkParty::count())->toBe(0);
        });
    });
});

describe('edit', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('works.edit', Work::factory()->create()))->assertRedirect(route('login'));
    });

    test('admins see the edit page with the ISRCs and the existing parties', function () {
        $work = Work::factory()->withIsrcs(2)->withParties()->create();

        $this->actingAs($this->admin)
            ->get(route('works.edit', $work))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('works/edit')
                ->where('work.data.id', $work->id)
                ->has('work.data.isrcs', 2)
                ->has('parties.data', 3)
            );
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('works.edit', Work::factory()->create()))
            ->assertForbidden();
    });
});

describe('update', function () {
    test('updates the work and redirects to it', function () {
        $work = Work::factory()->create(['english_title' => 'Old Title']);

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload([
                'macp_work_id' => $work->macp_work_id,
                'effective_date' => '2026-03-01',
                'english_title' => 'New Title',
                'registration_status' => 'accepted',
            ]))
            ->assertRedirect(route('works.show', $work))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        expect($work->refresh()->english_title)->toBe('New Title')
            ->and($work->registration_status)->toBe(RegistrationStatus::Accepted)
            ->and($work->tap_number)->toBe('TAP-000001');
    });

    test('can keep its own macp work id but not take another one', function () {
        $work = Work::factory()->create(['macp_work_id' => 'MACP-A']);
        Work::factory()->create(['macp_work_id' => 'MACP-B']);

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload(['macp_work_id' => 'MACP-A', 'effective_date' => '2026-03-01']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload(['macp_work_id' => 'MACP-B', 'effective_date' => '2026-03-01']))
            ->assertSessionHasErrors('macp_work_id');
    });

    test('replaces the ISRC list, keeping the ones that are still listed', function () {
        $work = Work::factory()->create();
        $work->isrcs()->createMany([['isrc' => 'MYABC2600001'], ['isrc' => 'MYABC2600002']]);
        $keptId = $work->isrcs()->where('isrc', 'MYABC2600001')->value('id');

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload([
                'macp_work_id' => $work->macp_work_id,
                'effective_date' => '2026-03-01',
                'isrcs' => ['MYABC2600001', 'MYABC2600003'],
            ]));

        expect($work->isrcs()->pluck('isrc')->all())->toBe(['MYABC2600001', 'MYABC2600003'])
            ->and($work->isrcs()->where('isrc', 'MYABC2600001')->value('id'))->toBe($keptId);
    });

    test('removes every ISRC when none is sent', function () {
        $work = Work::factory()->withIsrcs(2)->create();

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload(['macp_work_id' => $work->macp_work_id, 'effective_date' => '2026-03-01']));

        expect($work->isrcs()->count())->toBe(0);
    });

    test('leaves the parties untouched, even when some are sent', function () {
        $work = Work::factory()->withParties()->create();
        $columns = ['id', 'performance_share', 'effective_start_date', 'effective_end_date'];
        $before = $work->parties()->get($columns)->toArray();
        $writer = Writer::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload([
                'macp_work_id' => $work->macp_work_id,
                'effective_date' => '2026-03-01',
                'parties' => [partyPayload(['writer_id' => $writer->id])],
            ]))
            ->assertSessionHasNoErrors();

        expect($work->parties()->get($columns)->toArray())->toBe($before)
            ->and($work->parties()->count())->toBe(3);
    });

    test('needs an effective date', function () {
        $work = Work::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload(['macp_work_id' => $work->macp_work_id]))
            ->assertSessionHasErrors('effective_date');
    });

    test('records only what changed in the history, dated by the effective date', function () {
        $work = Work::factory()->create(['english_title' => 'Old Title', 'genre' => 'Pop']);

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload([
                'macp_work_id' => $work->macp_work_id,
                'english_title' => 'New Title',
                'genre' => 'Pop',
                'effective_date' => '2026-03-01',
                'remarks' => 'Retitled by the client',
            ]));

        $entry = RightsHistory::query()->where('work_id', $work->id)->sole();

        expect($entry->change_type)->toBe(RightsChangeType::WorkUpdated)
            ->and($entry->effective_date->toDateString())->toBe('2026-03-01')
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->remarks)->toBe('Retitled by the client')
            ->and($entry->previous_snapshot['english_title'])->toBe('Old Title')
            ->and($entry->new_snapshot['english_title'])->toBe('New Title')
            ->and($entry->new_snapshot)->not->toHaveKey('genre');
    });

    test('records an ISRC change in the history', function () {
        $work = Work::factory()->create();
        $work->isrcs()->create(['isrc' => 'MYABC2600001']);

        $this->actingAs($this->admin)
            ->put(route('works.update', $work), workPayload([
                'macp_work_id' => $work->macp_work_id,
                'english_title' => $work->english_title,
                'registration_status' => $work->registration_status->value,
                'conflict_status' => $work->conflict_status->value,
                'is_public_domain' => '0',
                'effective_date' => '2026-03-01',
                'isrcs' => ['MYABC2600002'],
            ]));

        $entry = RightsHistory::query()->where('work_id', $work->id)->sole();

        expect($entry->previous_snapshot['isrcs'])->toBe(['MYABC2600001'])
            ->and($entry->new_snapshot['isrcs'])->toBe(['MYABC2600002']);
    });

    test('regular users are forbidden', function () {
        $work = Work::factory()->create(['english_title' => 'Original']);

        $this->actingAs(User::factory()->create())
            ->put(route('works.update', $work), workPayload(['macp_work_id' => $work->macp_work_id, 'english_title' => 'Changed']))
            ->assertForbidden();

        expect($work->refresh()->english_title)->toBe('Original');
    });
});

describe('destroy', function () {
    test('admins can delete a work without losing the row or its parties', function () {
        $work = Work::factory()->withParties()->create();

        $this->actingAs($this->admin)
            ->delete(route('works.destroy', $work))
            ->assertRedirect(route('works.index'));

        expect(Work::find($work->id))->toBeNull()
            ->and(Work::withTrashed()->find($work->id))->not->toBeNull()
            ->and($work->parties()->count())->toBe(3);
    });

    test('regular users are forbidden', function () {
        $work = Work::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('works.destroy', $work))
            ->assertForbidden();

        expect(Work::find($work->id))->not->toBeNull();
    });
});
