<?php

use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('screen', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.song-ownership'))->assertRedirect(route('login'));
        $this->get(route('reports.export.song-ownership'))->assertRedirect(route('login'));
    });

    test('shows the current parties and the totals of every work', function () {
        $work = Work::factory()->create();
        WorkParty::factory()->create(['work_id' => $work->id, 'performance_share' => 60, 'mechanical_share' => 50, 'sync_share' => 25]);
        WorkParty::factory()->forPublisher()->create(['work_id' => $work->id, 'performance_share' => 40, 'mechanical_share' => 50]);
        WorkParty::factory()->ended('2020-01-31')->create(['work_id' => $work->id]);
        WorkParty::factory()->superseded()->create(['work_id' => $work->id]);
        Work::factory()->create();

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/song-ownership')
                ->has('rows.data', 2)
                ->has('rows.data.0.parties', 2)
                ->where('rows.data.0.totals', ['performance' => 100, 'mechanical' => 100, 'sync' => 25, 'lyrics' => null])
                ->has('rows.data.1.parties', 0)
            );
    });

    test('a date shows the parties that were in effect then', function () {
        $work = Work::factory()->create();
        $ended = WorkParty::factory()->ended('2023-12-31')->create(['work_id' => $work->id, 'effective_start_date' => '2023-01-01']);
        WorkParty::factory()->create(['work_id' => $work->id, 'effective_start_date' => '2024-01-01']);

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['as_of' => '2023-06-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data.0.parties', 1)
                ->where('rows.data.0.parties.0.id', $ended->id)
            );
    });

    test('a single work can be chosen by id or found by search', function () {
        $first = Work::factory()->create(['english_title' => 'Alpha Fake']);
        Work::factory()->create(['english_title' => 'Beta Fake']);

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['work_id' => $first->id]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('work.id', $first->id));

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['search' => 'Beta']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.title', 'Beta Fake'));
    });

    test('works can be narrowed by registration and conflict status', function () {
        Work::factory()->create(['registration_status' => 'accepted', 'conflict_status' => 'no_conflict']);
        $rejected = Work::factory()->create(['registration_status' => 'rejected', 'conflict_status' => 'share_conflict']);
        Work::factory()->create(['registration_status' => 'draft', 'conflict_status' => 'duplicate']);

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['registration_statuses' => ['accepted', 'rejected']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 2));

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['conflict_statuses' => ['share_conflict']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $rejected->id));

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['registration_statuses' => ['accepted'], 'conflict_statuses' => ['share_conflict']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('the controlled filter looks at the parties in effect on the date', function () {
        $controlled = Work::factory()->create();
        WorkParty::factory()->create(['work_id' => $controlled->id, 'is_controlled' => true, 'effective_start_date' => '2024-01-01']);
        $uncontrolled = Work::factory()->create();
        WorkParty::factory()->create(['work_id' => $uncontrolled->id, 'is_controlled' => false, 'effective_start_date' => '2024-01-01']);

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['controlled' => ['yes']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $controlled->id));

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['controlled' => ['no']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $uncontrolled->id));

        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['controlled' => ['yes'], 'as_of' => '2023-06-01']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('rejects an unknown status', function () {
        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['registration_statuses' => ['nonsense']]))
            ->assertSessionHasErrors('registration_statuses.0');
    });

    test('rejects a malformed date', function () {
        $this->actingAs($this->user)
            ->get(route('reports.song-ownership', ['as_of' => '06/01/2023']))
            ->assertSessionHasErrors('as_of');
    });
});

describe('export', function () {
    test('applies the same filters as the screen', function () {
        $kept = Work::factory()->create(['registration_status' => 'accepted']);
        WorkParty::factory()->create(['work_id' => $kept->id, 'contributor_name' => 'Kept Fake Composer']);
        $dropped = Work::factory()->create(['registration_status' => 'draft']);
        WorkParty::factory()->create(['work_id' => $dropped->id, 'contributor_name' => 'Dropped Fake Composer']);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.song-ownership', ['registration_statuses' => ['accepted']]))
            ->streamedContent();

        expect($content)->toContain('Kept Fake Composer')->not->toContain('Dropped Fake Composer');
    });

    test('downloads one row per party in effect', function () {
        $work = Work::factory()->create();
        WorkParty::factory()->create(['work_id' => $work->id, 'contributor_name' => 'Fake Composer', 'performance_share' => 50]);
        WorkParty::factory()->forPublisher()->create(['work_id' => $work->id, 'performance_share' => 50]);
        WorkParty::factory()->superseded()->create(['work_id' => $work->id]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.song-ownership'))
            ->assertDownload()
            ->streamedContent();

        $lines = array_values(array_filter(explode("\n", $content)));

        expect($lines)->toHaveCount(3)
            ->and($content)->toContain($work->tap_number)->toContain('Fake Composer')->toContain('Current');
    });
});
