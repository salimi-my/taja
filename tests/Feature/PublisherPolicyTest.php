<?php

use App\Models\Publisher;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('everyone signed in can view publishers', function (string $ability) {
    $publisher = Publisher::factory()->create();

    expect(Gate::forUser(User::factory()->create())->allows($ability, $publisher))->toBeTrue();
})->with(['view']);

test('everyone signed in can list publishers', function () {
    expect(Gate::forUser(User::factory()->create())->allows('viewAny', Publisher::class))->toBeTrue();
});

test('admins can create, change, delete and restore publishers', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, Publisher::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('admins can create publishers', function () {
    expect(Gate::forUser(User::factory()->admin()->create())->allows('create', Publisher::class))->toBeTrue();
});

test('regular users cannot change publishers', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, Publisher::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('regular users cannot create publishers', function () {
    expect(Gate::forUser(User::factory()->create())->denies('create', Publisher::class))->toBeTrue();
});
