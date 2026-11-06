<?php

use App\Models\User;
use App\Models\Writer;
use Illuminate\Support\Facades\Gate;

test('everyone signed in can view and list writers', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('view', Writer::factory()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', Writer::class))->toBeTrue();
});

test('admins can create, change, delete and restore writers', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, Writer::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('admins can create writers', function () {
    expect(Gate::forUser(User::factory()->admin()->create())->allows('create', Writer::class))->toBeTrue();
});

test('regular users cannot change writers', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, Writer::factory()->create()))->toBeTrue();
})->with(['update', 'delete', 'restore']);

test('regular users cannot create writers', function () {
    expect(Gate::forUser(User::factory()->create())->denies('create', Writer::class))->toBeTrue();
});
