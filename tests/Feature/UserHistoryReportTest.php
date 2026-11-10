<?php

use App\Enums\UserChangeType;
use App\Models\User;
use App\Models\UserHistory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('access', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.user-history'))->assertRedirect(route('login'));
        $this->get(route('reports.export.user-history'))->assertRedirect(route('login'));
    });

    test('regular users are forbidden', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.user-history'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.export.user-history'))->assertForbidden();
    });
});

describe('screen', function () {
    test('lists the entries, newest first', function () {
        $old = UserHistory::factory()->create(['recorded_at' => '2025-01-01 10:00:00']);
        $new = UserHistory::factory()->create(['recorded_at' => '2025-02-01 10:00:00']);

        $this->actingAs($this->admin)
            ->get(route('reports.user-history'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/user-history')
                ->has('rows.data', 2)
                ->where('rows.data.0.id', $new->id)
                ->where('rows.data.1.id', $old->id)
                ->has('changeTypes', count(UserChangeType::cases()))
            );
    });

    test('shows the copied actor name after the actor is deleted', function () {
        $actor = User::factory()->create(['name' => 'Former Actor']);
        UserHistory::factory()->byActor($actor)->create();
        $actor->delete();

        $this->actingAs($this->admin)
            ->get(route('reports.user-history'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.actor_name', 'Former Actor')
                ->has('users', 0)
            );
    });

    test('sorts by the name of the user when asked', function () {
        $second = UserHistory::factory()->create(['subject_name' => 'Zed Fake']);
        $first = UserHistory::factory()->create(['subject_name' => 'Abe Fake']);

        $this->actingAs($this->admin)
            ->get(route('reports.user-history', ['sort' => 'subject_name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.id', $first->id)
                ->where('rows.data.1.id', $second->id)
            );
    });

    test('searches the name and the email, treating wildcards literally', function () {
        $byName = UserHistory::factory()->create(['subject_name' => 'Searchable Fake']);
        $byEmail = UserHistory::factory()->create(['subject_email' => 'findme@example.com']);
        UserHistory::factory()->create(['subject_name' => 'Other Fake']);

        $this->actingAs($this->admin)
            ->get(route('reports.user-history', ['search' => 'Searchable']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $byName->id));

        $this->get(route('reports.user-history', ['search' => 'findme']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $byEmail->id));

        $this->get(route('reports.user-history', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 0));
    });

    test('filters by change type and by the user who made the change', function () {
        $author = User::factory()->create();
        $match = UserHistory::factory()->byActor($author)->create(['change_type' => UserChangeType::RoleChanged]);
        UserHistory::factory()->byActor($author)->create(['change_type' => UserChangeType::Created]);
        UserHistory::factory()->byActor($this->admin)->create(['change_type' => UserChangeType::RoleChanged]);

        $this->actingAs($this->admin)
            ->get(route('reports.user-history', ['change_types' => ['role_changed'], 'users' => [$author->id]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows.data', 1)
                ->where('rows.data.0.id', $match->id)
                ->has('users', 2)
            );
    });

    test('filters by the recorded date range', function () {
        $inside = UserHistory::factory()->create(['recorded_at' => '2025-03-10 09:00:00']);
        UserHistory::factory()->create(['recorded_at' => '2025-05-10 09:00:00']);

        $this->actingAs($this->admin)
            ->get(route('reports.user-history', ['recorded_from' => '2025-03-01', 'recorded_to' => '2025-03-31']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $inside->id));
    });

    test('rejects unknown filter values and sort columns', function () {
        $this->actingAs($this->admin)
            ->get(route('reports.user-history', ['change_types' => ['nonsense'], 'sort' => 'remarks']))
            ->assertSessionHasErrors(['change_types.0', 'sort']);
    });
});

describe('export', function () {
    test('downloads the filtered entries with the summary and the actor', function () {
        $author = User::factory()->create(['name' => 'Fake Author']);
        UserHistory::factory()->byActor($author)->create([
            'subject_name' => 'Exported Fake',
            'subject_email' => 'exported@example.com',
            'change_type' => UserChangeType::PasswordChanged,
            'remarks' => 'Fake remark',
        ]);
        UserHistory::factory()->create(['change_type' => UserChangeType::Deleted]);

        $content = $this->actingAs($this->admin)
            ->get(route('reports.export.user-history', ['change_types' => ['password_changed']]))
            ->assertDownload()
            ->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(2)
            ->and($content)->toContain('Exported Fake')->toContain('exported@example.com')->toContain('Password changed.')->toContain('Fake Author')->toContain('Fake remark');
    });
});
