<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('admins can manage other users', function (string $ability) {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($admin)->allows($ability, $other))->toBeTrue();
})->with(['update', 'delete', 'sendPasswordResetLink']);

test('admins can list and create users', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, User::class))->toBeTrue();
})->with(['viewAny', 'create']);

test('admins cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->denies('delete', $admin))->toBeTrue();
});

test('regular users cannot manage users', function (string $ability) {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, $other))->toBeTrue();
})->with(['update', 'delete', 'sendPasswordResetLink']);

test('regular users cannot list or create users', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, User::class))->toBeTrue();
})->with(['viewAny', 'create']);
