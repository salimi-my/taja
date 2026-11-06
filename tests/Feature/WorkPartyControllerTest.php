<?php

use App\Enums\PartyRole;
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
 * The form values of the change and correct dialogs. They describe a row that
 * owns 40% of performance and mechanical for the whole world.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function partyRightsInput(array $overrides = []): array
{
    return [
        'role' => 'C',
        'performance_share' => '40',
        'mechanical_share' => '40',
        'is_controlled' => '1',
        'territories_controlled' => ['WW'],
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seed(CountrySeeder::class);

    $this->work = Work::factory()->create(['administration_start_date' => '2024-01-01']);
    $this->party = WorkParty::factory()->forWriter()->create([
        'work_id' => $this->work->id,
        'role' => PartyRole::C,
        'performance_share' => 60,
        'mechanical_share' => 60,
        'effective_start_date' => '2024-01-01',
    ]);
});

describe('store', function () {
    test('adds a party from a date and logs it', function () {
        $writer = Writer::factory()->create(['name' => 'Fake Author']);

        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput([
                'role' => 'A',
                'writer_id' => $writer->id,
                'performance_share' => '30',
                'mechanical_share' => '30',
                'effective_start_date' => '2025-02-02',
                'remarks' => 'Joined the work',
            ]))
            ->assertRedirect(route('works.show', $this->work))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $added = WorkParty::where('writer_id', $writer->id)->sole();
        $entry = RightsHistory::where('work_party_id', $added->id)->sole();

        expect($added->contributor_name)->toBe('Fake Author')
            ->and($added->effective_start_date->toDateString())->toBe('2025-02-02')
            ->and($entry->change_type)->toBe(RightsChangeType::PartyAdded)
            ->and($entry->effective_date->toDateString())->toBe('2025-02-02')
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->remarks)->toBe('Joined the work')
            ->and($entry->new_snapshot['role'])->toBe('A');
    });

    test('needs an effective start date', function () {
        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput(['writer_id' => Writer::factory()->create()->id]))
            ->assertSessionHasErrors('effective_start_date');
    });

    test('rejects shares that push a rights type over 100 and writes nothing', function () {
        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput([
                'writer_id' => Writer::factory()->create()->id,
                'performance_share' => '50',
                'effective_start_date' => '2024-06-01',
            ]))
            ->assertSessionHasErrors('shares');

        expect($this->work->parties()->count())->toBe(1)
            ->and(RightsHistory::count())->toBe(0);
    });

    test('rejects the same party with the same role for overlapping dates', function () {
        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput([
                'writer_id' => $this->party->writer_id,
                'performance_share' => '10',
                'mechanical_share' => '10',
                'effective_start_date' => '2024-06-01',
            ]))
            ->assertSessionHasErrors('party');

        expect($this->work->parties()->count())->toBe(1);
    });

    test('a publisher role needs a publisher', function () {
        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput([
                'role' => 'E',
                'writer_id' => Writer::factory()->create()->id,
                'performance_share' => '10',
                'effective_start_date' => '2025-01-01',
            ]))
            ->assertSessionHasErrors('publisher_id');

        $this->actingAs($this->admin)
            ->post(route('works.parties.store', $this->work), partyRightsInput([
                'role' => 'E',
                'publisher_id' => Publisher::factory()->create()->id,
                'performance_share' => '10',
                'mechanical_share' => '10',
                'effective_start_date' => '2025-01-01',
            ]))
            ->assertSessionHasNoErrors();
    });

    test('regular users are forbidden and guests are redirected', function () {
        $input = partyRightsInput(['writer_id' => Writer::factory()->create()->id, 'effective_start_date' => '2025-01-01']);

        $this->actingAs(User::factory()->create())
            ->post(route('works.parties.store', $this->work), $input)
            ->assertForbidden();

        auth()->logout();

        $this->post(route('works.parties.store', $this->work), $input)->assertRedirect(route('login'));

        expect($this->work->parties()->count())->toBe(1);
    });
});

describe('change', function () {
    test('ends the open row the day before and opens a new one', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-01-01', 'remarks' => 'Renegotiated']))
            ->assertRedirect(route('works.show', $this->work))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $new = $this->work->parties()->where('id', '!=', $this->party->id)->sole();
        $entry = RightsHistory::where('work_party_id', $new->id)->sole();

        expect($this->party->refresh()->effective_end_date->toDateString())->toBe('2024-12-31')
            ->and($this->party->performance_share)->toBe('60.00')
            ->and($this->party->superseded_at)->toBeNull()
            ->and($new->effective_start_date->toDateString())->toBe('2025-01-01')
            ->and($new->effective_end_date)->toBeNull()
            ->and($new->performance_share)->toBe('40.00')
            ->and($new->writer_id)->toBe($this->party->writer_id)
            ->and($new->contributor_name)->toBe($this->party->contributor_name)
            ->and($entry->change_type)->toBe(RightsChangeType::ShareUpdated)
            ->and($entry->effective_date->toDateString())->toBe('2025-01-01')
            ->and($entry->user_id)->toBe($this->admin->id)
            ->and($entry->remarks)->toBe('Renegotiated')
            ->and($entry->previous_snapshot['performance_share'])->toBe('60.00')
            ->and($entry->new_snapshot['performance_share'])->toBe('40.00');
    });

    test('logs a territory change when only the territories differ', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput([
                'effective_date' => '2025-01-01',
                'performance_share' => '60',
                'mechanical_share' => '60',
                'territories_controlled' => ['MY', 'SG'],
            ]))
            ->assertSessionHasNoErrors();

        expect(RightsHistory::sole()->change_type)->toBe(RightsChangeType::TerritoryChanged);
    });

    test('rebuilds the work as it was before and after the change date', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-01-01']));

        $before = $this->work->parties()->effectiveOn('2024-12-31')->get();
        $after = $this->work->parties()->effectiveOn('2025-01-01')->get();

        expect($before)->toHaveCount(1)
            ->and($before->first()->performance_share)->toBe('60.00')
            ->and($after)->toHaveCount(1)
            ->and($after->first()->performance_share)->toBe('40.00')
            ->and($this->work->currentParties()->sole()->performance_share)->toBe('40.00');
    });

    test('needs an effective date', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput())
            ->assertSessionHasErrors('effective_date');
    });

    test('the date must be after the row started', function (string $date) {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => $date]))
            ->assertSessionHasErrors('effective_date');

        expect($this->work->parties()->count())->toBe(1)
            ->and($this->party->refresh()->effective_end_date)->toBeNull()
            ->and(RightsHistory::count())->toBe(0);
    })->with(['the start date itself' => '2024-01-01', 'before the start' => '2023-06-01']);

    test('rejects a change that alters nothing', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput([
                'effective_date' => '2025-01-01',
                'performance_share' => '60',
                'mechanical_share' => '60',
            ]))
            ->assertSessionHasErrors('party');

        expect($this->work->parties()->count())->toBe(1);
    });

    test('rejects a row that has already ended', function () {
        $this->party->update(['effective_end_date' => '2024-12-31']);

        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-06-01']))
            ->assertSessionHasErrors('party');
    });

    test('rolls everything back when the new shares add up to more than 100', function () {
        WorkParty::factory()->forWriter()->create([
            'work_id' => $this->work->id,
            'performance_share' => 40,
            'mechanical_share' => 40,
            'effective_start_date' => '2024-01-01',
        ]);

        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-01-01', 'performance_share' => '70']))
            ->assertSessionHasErrors('shares');

        expect($this->work->parties()->count())->toBe(2)
            ->and($this->party->refresh()->effective_end_date)->toBeNull()
            ->and(RightsHistory::count())->toBe(0);
    });

    test('a writer row cannot change to a publisher role', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-01-01', 'role' => 'E']))
            ->assertSessionHasErrors('role');
    });

    test('a row of another work is not found', function () {
        $other = Work::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('works.parties.change', [$other, $this->party]), partyRightsInput(['effective_date' => '2025-01-01']))
            ->assertNotFound();
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->put(route('works.parties.change', [$this->work, $this->party]), partyRightsInput(['effective_date' => '2025-01-01']))
            ->assertForbidden();

        expect($this->work->parties()->count())->toBe(1);
    });
});

describe('correct', function () {
    test('supersedes the old row and keeps its dates on the replacement', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-01-01',
                'performance_share' => '55',
                'mechanical_share' => '55',
                'remarks' => 'Typo in the split',
            ]))
            ->assertRedirect(route('works.show', $this->work));

        $new = $this->work->parties()->where('id', '!=', $this->party->id)->sole();
        $entry = RightsHistory::where('work_party_id', $new->id)->sole();

        expect($this->party->refresh()->superseded_at)->not->toBeNull()
            ->and($this->party->performance_share)->toBe('60.00')
            ->and($new->effective_start_date->toDateString())->toBe('2024-01-01')
            ->and($new->effective_end_date)->toBeNull()
            ->and($new->performance_share)->toBe('55.00')
            ->and($entry->change_type)->toBe(RightsChangeType::Correction)
            ->and($entry->previous_snapshot['performance_share'])->toBe('60.00')
            ->and($entry->new_snapshot['performance_share'])->toBe('55.00')
            ->and($entry->remarks)->toBe('Typo in the split');
    });

    test('hides the old row from the current and point-in-time views but not from the history', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-01-01',
                'performance_share' => '55',
                'mechanical_share' => '55',
            ]));

        expect($this->work->currentParties()->pluck('performance_share')->all())->toBe(['55.00'])
            ->and($this->work->parties()->effectiveOn('2024-06-01')->pluck('performance_share')->all())->toBe(['55.00'])
            ->and($this->work->parties()->count())->toBe(2);

        $this->actingAs($this->admin)
            ->get(route('works.show', $this->work))
            ->assertInertia(fn (Assert $page) => $page
                ->has('parties.data', 1)
                ->loadDeferredProps(fn (Assert $page) => $page
                    ->has('history.data', 1)
                    ->where('history.data.0.previous_snapshot.performance_share', '60.00')
                )
            );
    });

    test('logs a dates-changed entry when only the dates differ', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-03-01',
                'performance_share' => '60',
                'mechanical_share' => '60',
            ]))
            ->assertSessionHasNoErrors();

        $entry = RightsHistory::sole();

        expect($entry->change_type)->toBe(RightsChangeType::DatesChanged)
            ->and($entry->effective_date->toDateString())->toBe('2024-03-01');
    });

    test('can correct a row that has already ended', function () {
        $this->party->update(['effective_end_date' => '2024-12-31']);

        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-01-01',
                'effective_end_date' => '2025-01-31',
                'performance_share' => '60',
                'mechanical_share' => '60',
            ]))
            ->assertSessionHasNoErrors();

        expect($this->work->parties()->whereNull('superseded_at')->sole()->effective_end_date->toDateString())->toBe('2025-01-31');
    });

    test('the end date cannot be before the start date', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-06-01',
                'effective_end_date' => '2024-05-01',
            ]))
            ->assertSessionHasErrors('effective_end_date');
    });

    test('rejects a row that was already corrected', function () {
        $this->party->update(['superseded_at' => now()]);

        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput(['effective_start_date' => '2024-01-01']))
            ->assertSessionHasErrors('party');
    });

    test('rejects a correction that alters nothing', function () {
        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput([
                'effective_start_date' => '2024-01-01',
                'performance_share' => '60',
                'mechanical_share' => '60',
            ]))
            ->assertSessionHasErrors('party');

        expect(RightsHistory::count())->toBe(0);
    });

    test('rolls everything back when the corrected shares add up to more than 100', function () {
        WorkParty::factory()->forWriter()->create([
            'work_id' => $this->work->id,
            'performance_share' => 50,
            'mechanical_share' => 50,
            'effective_start_date' => '2024-01-01',
        ]);

        $this->actingAs($this->admin)
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput(['effective_start_date' => '2024-01-01', 'performance_share' => '70']))
            ->assertSessionHasErrors('shares');

        expect($this->party->refresh()->superseded_at)->toBeNull()
            ->and($this->work->parties()->count())->toBe(2)
            ->and(RightsHistory::count())->toBe(0);
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->put(route('works.parties.correct', [$this->work, $this->party]), partyRightsInput(['effective_start_date' => '2024-01-01']))
            ->assertForbidden();
    });
});

describe('destroy', function () {
    test('ends the row the day before the date and logs the removal', function () {
        $this->actingAs($this->admin)
            ->delete(route('works.parties.destroy', [$this->work, $this->party]), ['effective_date' => '2025-07-01', 'remarks' => 'Left the work'])
            ->assertRedirect(route('works.show', $this->work))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $entry = RightsHistory::sole();

        expect($this->party->refresh()->effective_end_date->toDateString())->toBe('2025-06-30')
            ->and(WorkParty::count())->toBe(1)
            ->and($entry->change_type)->toBe(RightsChangeType::PartyRemoved)
            ->and($entry->work_party_id)->toBe($this->party->id)
            ->and($entry->effective_date->toDateString())->toBe('2025-07-01')
            ->and($entry->previous_snapshot['performance_share'])->toBe('60.00')
            ->and($entry->new_snapshot)->toBeNull()
            ->and($entry->remarks)->toBe('Left the work')
            ->and($this->work->parties()->effectiveOn('2025-06-30')->count())->toBe(1)
            ->and($this->work->parties()->effectiveOn('2025-07-01')->count())->toBe(0);
    });

    test('needs a date after the row started', function () {
        $this->actingAs($this->admin)
            ->delete(route('works.parties.destroy', [$this->work, $this->party]))
            ->assertSessionHasErrors('effective_date');

        $this->actingAs($this->admin)
            ->delete(route('works.parties.destroy', [$this->work, $this->party]), ['effective_date' => '2024-01-01'])
            ->assertSessionHasErrors('effective_date');

        expect($this->party->refresh()->effective_end_date)->toBeNull()
            ->and(RightsHistory::count())->toBe(0);
    });

    test('rejects a row that has already ended', function () {
        $this->party->update(['effective_end_date' => '2024-12-31']);

        $this->actingAs($this->admin)
            ->delete(route('works.parties.destroy', [$this->work, $this->party]), ['effective_date' => '2025-07-01'])
            ->assertSessionHasErrors('party');
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->delete(route('works.parties.destroy', [$this->work, $this->party]), ['effective_date' => '2025-07-01'])
            ->assertForbidden();

        expect($this->party->refresh()->effective_end_date)->toBeNull();
    });
});
