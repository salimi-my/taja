<?php

use App\Enums\ConflictStatus;
use App\Enums\CsvImportStatus;
use App\Enums\RegistrationStatus;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the catalogue counts and the incomplete splits', function () {
    $complete = Work::factory()->create(['registration_status' => RegistrationStatus::Accepted]);
    WorkParty::factory()->create(['work_id' => $complete->id]);
    Work::factory()->count(2)->create();
    Work::factory()->create()->delete();
    Writer::factory()->count(3)->create();
    Publisher::factory()->count(4)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.works', 3)
            ->where('stats.writers', 4)
            ->where('stats.publishers', 4)
            ->where('stats.incomplete_splits', 2)
            ->where('registrationBreakdown', fn ($breakdown) => collect($breakdown)->firstWhere('status', 'accepted')['total'] === 1
                && collect($breakdown)->firstWhere('status', 'draft')['total'] === 2
                && collect($breakdown)->sum('total') === 3)
        );
});

test('the dashboard lists the latest rights history first', function () {
    $old = RightsHistory::factory()->create(['recorded_at' => now()->subDays(3)]);
    $new = RightsHistory::factory()->create(['recorded_at' => now()->subDay()]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentHistory.data', 2)
            ->where('recentHistory.data.0.id', $new->id)
            ->where('recentHistory.data.1.id', $old->id)
            ->where('recentHistory.data.0.work.tap_number', $new->work->tap_number)
        );
});

test('only admins see the recent imports', function () {
    $admin = User::factory()->admin()->create();
    $import = CsvImport::factory()->create(['user_id' => $admin->id, 'original_filename' => 'fake-works.csv']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentImports', 1)
            ->where('recentImports.0.id', $import->id)
            ->where('recentImports.0.original_filename', 'fake-works.csv')
            ->where('recentImports.0.uploaded_by', $admin->name)
            ->where('recentImports.0.uploaded_by_avatar_url', $admin->avatar_url)
        );

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('recentImports', null));
});

test('the dashboard counts what was added this week and the week before', function () {
    Work::factory()->count(2)->create(['created_at' => now()->subDays(2)]);
    Work::factory()->create(['created_at' => now()->subDays(10)]);
    Work::factory()->create(['created_at' => now()->subDays(40)]);
    Work::factory()->create(['created_at' => now()->subDay()])->delete();
    Writer::factory()->create(['created_at' => now()->subDays(9)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.works', 4)
            ->where('stats.works_this_week', 2)
            ->where('stats.works_last_week', 1)
            ->where('stats.writers_this_week', 0)
            ->where('stats.writers_last_week', 1)
        );
});

test('the attention checks count conflicts and rejected registrations', function () {
    Work::factory()->count(2)->create(['conflict_status' => ConflictStatus::ShareConflict]);
    Work::factory()->create(['conflict_status' => ConflictStatus::Duplicate]);
    Work::factory()->create(['registration_status' => RegistrationStatus::Rejected]);
    Work::factory()->create(['conflict_status' => ConflictStatus::SocietyQuery])->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attention', fn ($items) => collect($items)->firstWhere('key', 'works_in_conflict')['count'] === 3
                && collect($items)->firstWhere('key', 'rejected_registrations')['count'] === 1
                && collect($items)->firstWhere('key', 'incomplete_splits')['count'] === 4)
        );
});

test('only admins get the failed imports check, pointing at the latest failed import', function () {
    $admin = User::factory()->admin()->create();
    CsvImport::factory()->create(['status' => CsvImportStatus::Failed]);
    $latest = CsvImport::factory()->create(['status' => CsvImportStatus::Failed]);
    CsvImport::factory()->imported()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attention', fn ($items) => collect($items)->firstWhere('key', 'failed_imports') === [
                'key' => 'failed_imports',
                'count' => 2,
                'target_id' => $latest->id,
            ])
        );

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attention', fn ($items) => collect($items)->doesntContain('key', 'failed_imports'))
        );
});

test('the activity chart is deferred, covers 30 days by default and fills empty days', function () {
    $work = Work::factory()->create(['created_at' => now()]);
    RightsHistory::factory()->count(2)->create(['work_id' => $work->id, 'recorded_at' => now()->subDays(2)]);
    RightsHistory::factory()->create(['work_id' => $work->id, 'recorded_at' => now()->subDays(45)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityDays', 30)
            ->missing('activity')
            ->loadDeferredProps(fn (Assert $page) => $page
                ->has('activity', 30)
                ->where('activity.29.date', now()->toDateString())
                ->where('activity.29.works', 1)
                ->where('activity.27.changes', 2)
                ->where('activity.0.works', 0)
            )
        );
});

test('the activity chart accepts a period and rejects other values', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['days' => 7]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('activityDays', 7)
            ->loadDeferredProps(fn (Assert $page) => $page->has('activity', 7))
        );

    $this->get(route('dashboard', ['days' => 5]))->assertSessionHasErrors('days');
});

test('the conflict breakdown lists every status with its works', function () {
    Work::factory()->count(2)->create(['conflict_status' => ConflictStatus::ShareConflict]);
    Work::factory()->create();
    Work::factory()->create(['conflict_status' => ConflictStatus::Duplicate])->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $page) => $page
                ->has('conflictBreakdown', count(ConflictStatus::cases()))
                ->where('conflictBreakdown', fn ($breakdown) => collect($breakdown)->firstWhere('value', 'share_conflict')['total'] === 2
                    && collect($breakdown)->firstWhere('value', 'no_conflict')['total'] === 1
                    && collect($breakdown)->firstWhere('value', 'duplicate')['total'] === 0)
            )
        );
});
