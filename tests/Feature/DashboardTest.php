<?php

use App\Models\User;
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

test('the dashboard shares user statistics and the signups chart', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();
    User::factory()->unverified()->create(['created_at' => now()->subDays(60)]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.total_users', 4)
            ->where('stats.verified_users', 3)
            ->where('stats.admin_users', 1)
            ->where('stats.new_users', 3)
            ->has('signups', 14)
            ->where('signups.13.signups', 3)
            ->has('recentUsers.data', 4)
        );
});
