<?php

use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;

test('everyone signed in can view and list works', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('view', Work::factory()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', Work::class))->toBeTrue();
});

test('admins can create, change, delete and restore works', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, Work::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('admins can create works', function () {
    expect(Gate::forUser(User::factory()->admin()->create())->allows('create', Work::class))->toBeTrue();
});

test('regular users cannot change works', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, Work::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('regular users cannot create works', function () {
    expect(Gate::forUser(User::factory()->create())->denies('create', Work::class))->toBeTrue();
});
