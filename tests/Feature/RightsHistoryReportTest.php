<?php

use App\Enums\RightsChangeType;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function historyEntry(array $attributes = []): RightsHistory
{
    return RightsHistory::factory()->create($attributes);
}

describe('screen', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.rights-history'))->assertRedirect(route('login'));
        $this->get(route('reports.export.rights-history'))->assertRedirect(route('login'));
    });

    test('lists the entries of every work, newest effective date first, with the work', function () {
        $old = historyEntry(['effective_date' => '2024-01-01']);
        $new = historyEntry(['effective_date' => '2025-01-01']);

        $this->actingAs($this->user)
            ->get(route('reports.rights-history'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/rights-history')
                ->has('rows.data', 2)
                ->where('rows.data.0.id', $new->id)
                ->where('rows.data.1.id', $old->id)
                ->where('rows.data.0.work.tap_number', $new->work->tap_number)
                ->has('changeTypes')
            );
    });

    test('sorts by the recorded date when asked', function () {
        $first = historyEntry(['recorded_at' => '2025-03-01 10:00:00']);
        $second = historyEntry(['recorded_at' => '2025-01-01 10:00:00']);

        $this->actingAs($this->user)
            ->get(route('reports.rights-history', ['sort' => 'recorded_at', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.id', $second->id)
                ->where('rows.data.1.id', $first->id)
            );
    });

    test('filters by work', function () {
        $entry = historyEntry();
        historyEntry();

        $this->actingAs($this->user)
            ->get(route('reports.rights-history', ['work_id' => $entry->work_id]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('work.id', $entry->work_id));
    });

    test('filters by work search, including works that were deleted', function () {
        $work = Work::factory()->create(['english_title' => 'Gone Fake']);
        $entry = historyEntry(['work_id' => $work->id]);
        historyEntry();
        $work->delete();

        $this->actingAs($this->user)
            ->get(route('reports.rights-history', ['search' => 'Gone Fake']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $entry->id));
    });

    test('filters by change type and by user', function () {
        $author = User::factory()->create();
        $match = historyEntry(['change_type' => RightsChangeType::Correction, 'user_id' => $author->id]);
        historyEntry(['change_type' => RightsChangeType::Correction, 'user_id' => $this->user->id]);
        historyEntry(['change_type' => RightsChangeType::PartyAdded, 'user_id' => $author->id]);

        $this->actingAs($this->user)
            ->get(route('reports.rights-history', ['change_types' => ['correction'], 'users' => [$author->id]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.id', $match->id)
                ->has('users', 2)
            );
    });

    test('filters by effective and recorded date ranges', function () {
        $inside = historyEntry(['effective_date' => '2025-02-15', 'recorded_at' => '2025-03-10 09:00:00']);
        historyEntry(['effective_date' => '2025-02-15', 'recorded_at' => '2025-05-10 09:00:00']);
        historyEntry(['effective_date' => '2024-02-15', 'recorded_at' => '2025-03-10 09:00:00']);

        $this->actingAs($this->user)
            ->get(route('reports.rights-history', [
                'effective_from' => '2025-02-01',
                'effective_to' => '2025-02-28',
                'recorded_from' => '2025-03-01',
                'recorded_to' => '2025-03-31',
            ]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $inside->id));
    });

    test('rejects an unknown change type and sort column', function () {
        $this->actingAs($this->user)
            ->get(route('reports.rights-history', ['change_types' => ['nonsense'], 'sort' => 'remarks']))
            ->assertSessionHasErrors(['change_types.0', 'sort']);
    });
});

describe('export', function () {
    test('downloads the filtered entries with the summary and user', function () {
        $author = User::factory()->create(['name' => 'Fake Author']);
        $entry = historyEntry(['user_id' => $author->id, 'change_type' => RightsChangeType::WorkCreated, 'remarks' => 'Fake remark']);
        historyEntry(['change_type' => RightsChangeType::Correction]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.rights-history', ['change_types' => ['work_created']]))
            ->assertDownload()
            ->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(2)
            ->and($content)->toContain($entry->work->tap_number)->toContain('Work created')->toContain('Fake Author')->toContain('Fake remark');
    });
});
