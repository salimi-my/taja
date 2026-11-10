<?php

use App\Enums\MasterChangeType;
use App\Models\MasterHistory;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Writer;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('screen', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.master-history'))->assertRedirect(route('login'));
        $this->get(route('reports.export.master-history'))->assertRedirect(route('login'));
    });

    test('lists the entries of writers and publishers, newest first', function () {
        $old = MasterHistory::factory()->create(['recorded_at' => '2025-01-01 10:00:00']);
        $new = MasterHistory::factory()->forSubject(Publisher::factory()->create())->create(['recorded_at' => '2025-02-01 10:00:00']);

        $this->actingAs($this->user)
            ->get(route('reports.master-history'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/master-history')
                ->has('rows.data', 2)
                ->where('rows.data.0.id', $new->id)
                ->where('rows.data.0.subject.type', 'publisher')
                ->where('rows.data.0.subject.is_deleted', false)
                ->where('rows.data.1.id', $old->id)
                ->has('changeTypes', 4)
                ->has('subjectTypes', 2)
            );
    });

    test('marks the entries of a deleted record', function () {
        $writer = Writer::factory()->create();
        MasterHistory::factory()->forSubject($writer)->create();
        $writer->delete();

        $this->actingAs($this->user)
            ->get(route('reports.master-history'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.subject.is_deleted', true)
            );
    });

    test('sorts by the name of the record when asked', function () {
        $second = MasterHistory::factory()->create(['subject_name' => 'Zed Fake']);
        $first = MasterHistory::factory()->create(['subject_name' => 'Abe Fake']);

        $this->actingAs($this->user)
            ->get(route('reports.master-history', ['sort' => 'subject_name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.id', $first->id)
                ->where('rows.data.1.id', $second->id)
            );
    });

    test('searches the name and the code of the record, treating wildcards literally', function () {
        $byName = MasterHistory::factory()->create(['subject_name' => 'Searchable Fake']);
        $byCode = MasterHistory::factory()->create(['subject_code' => 'WRT-424242']);
        MasterHistory::factory()->create(['subject_name' => 'Other Fake']);

        $this->actingAs($this->user)
            ->get(route('reports.master-history', ['search' => 'Searchable']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $byName->id));

        $this->get(route('reports.master-history', ['search' => '424242']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $byCode->id));

        $this->get(route('reports.master-history', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('filters by record type, change type and user', function () {
        $author = User::factory()->create();
        $match = MasterHistory::factory()->forSubject(Publisher::factory()->create())->create([
            'change_type' => MasterChangeType::Deleted,
            'user_id' => $author->id,
        ]);
        MasterHistory::factory()->create(['change_type' => MasterChangeType::Deleted, 'user_id' => $author->id]);
        MasterHistory::factory()->forSubject(Publisher::factory()->create())->create(['change_type' => MasterChangeType::Created, 'user_id' => $author->id]);
        MasterHistory::factory()->forSubject(Publisher::factory()->create())->create(['change_type' => MasterChangeType::Deleted, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('reports.master-history', ['types' => ['publisher'], 'change_types' => ['deleted'], 'users' => [$author->id]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.id', $match->id)
                ->has('users', 2)
            );
    });

    test('filters by the recorded date range', function () {
        $inside = MasterHistory::factory()->create(['recorded_at' => '2025-03-10 09:00:00']);
        MasterHistory::factory()->create(['recorded_at' => '2025-05-10 09:00:00']);

        $this->actingAs($this->user)
            ->get(route('reports.master-history', ['recorded_from' => '2025-03-01', 'recorded_to' => '2025-03-31']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $inside->id));
    });

    test('rejects unknown filter values and sort columns', function () {
        $this->actingAs($this->user)
            ->get(route('reports.master-history', ['types' => ['work'], 'change_types' => ['nonsense'], 'sort' => 'remarks']))
            ->assertSessionHasErrors(['types.0', 'change_types.0', 'sort']);
    });
});

describe('export', function () {
    test('downloads the filtered entries with the summary and user', function () {
        $author = User::factory()->create(['name' => 'Fake Author']);
        MasterHistory::factory()->create([
            'subject_name' => 'Exported Fake',
            'subject_code' => 'WRT-000777',
            'change_type' => MasterChangeType::Created,
            'user_id' => $author->id,
            'remarks' => 'Fake remark',
        ]);
        MasterHistory::factory()->create(['change_type' => MasterChangeType::Deleted]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.master-history', ['change_types' => ['created']]))
            ->assertDownload()
            ->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(2)
            ->and($content)->toContain('Exported Fake')->toContain('WRT-000777')->toContain('Writer created.')->toContain('Fake Author')->toContain('Fake remark');
    });
});
