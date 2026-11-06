<?php

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
        );

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('recentImports', null));
});
