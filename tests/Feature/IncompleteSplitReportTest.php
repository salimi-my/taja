<?php

use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * A work with the given party shares (performance and mechanical are the same).
 *
 * @param  list<int|float>  $shares
 * @param  array<string, mixed>  $partyAttributes
 */
function workWithShares(array $shares, array $partyAttributes = []): Work
{
    $work = Work::factory()->create();

    foreach ($shares as $share) {
        WorkParty::factory()->create([
            'work_id' => $work->id,
            'performance_share' => $share,
            'mechanical_share' => $share,
            ...$partyAttributes,
        ]);
    }

    return $work;
}

describe('screen', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.incomplete-split'))->assertRedirect(route('login'));
        $this->get(route('reports.export.incomplete-split'))->assertRedirect(route('login'));
    });

    test('lists works without parties and works whose totals are not 100, with the reasons', function () {
        $empty = Work::factory()->create();
        $short = workWithShares([40, 40]);
        workWithShares([50, 50]);

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split', ['sort' => 'created_at']))
            ->assertSessionHasErrors('sort');

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/incomplete-split')
                ->has('rows.data', 2)
                ->where('rows.data.0.id', $empty->id)
                ->where('rows.data.0.reasons', ['No parties in effect'])
                ->where('rows.data.0.parties_count', 0)
                ->where('rows.data.1.id', $short->id)
                ->where('rows.data.1.totals.performance', 80)
                ->where('rows.data.1.reasons', ['Performance shares add up to 80%', 'Mechanical shares add up to 80%'])
            );
    });

    test('sums that add up to 100 with decimals are complete', function () {
        workWithShares([33.33, 33.33, 33.34]);

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split'))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('sync and lyrics are only checked when a party has a value', function () {
        $work = workWithShares([100]);
        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split'))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));

        WorkParty::where('work_id', $work->id)->update(['sync_share' => 60]);

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.totals.sync', 60)
                ->where('rows.data.0.totals.lyrics', null)
                ->where('rows.data.0.reasons', ['Sync shares add up to 60%'])
            );
    });

    test('ended and superseded rows do not count and uncontrolled rows do', function () {
        $work = workWithShares([50]);
        WorkParty::factory()->create(['work_id' => $work->id, 'performance_share' => 50, 'mechanical_share' => 50, 'is_controlled' => false]);
        WorkParty::factory()->ended('2020-01-31')->create(['work_id' => $work->id, 'performance_share' => 30, 'mechanical_share' => 30]);
        WorkParty::factory()->superseded()->create(['work_id' => $work->id, 'performance_share' => 30, 'mechanical_share' => 30]);

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split'))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('deleted works are not listed and the search narrows the list', function () {
        Work::factory()->create(['english_title' => 'Gone Fake'])->delete();
        Work::factory()->create(['english_title' => 'Alpha Fake']);
        Work::factory()->create(['english_title' => 'Beta Fake']);

        $this->actingAs($this->user)
            ->get(route('reports.incomplete-split', ['search' => 'Alpha']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.title', 'Alpha Fake'));
    });
});

describe('export', function () {
    test('downloads the incomplete works with totals and issues', function () {
        $short = workWithShares([40]);
        workWithShares([100]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.incomplete-split'))
            ->assertDownload()
            ->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(2)
            ->and($content)->toContain($short->tap_number)->toContain('Performance shares add up to 40%; Mechanical shares add up to 40%');
    });
});
