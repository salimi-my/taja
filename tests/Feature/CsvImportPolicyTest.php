<?php

use App\Models\CsvImport;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('admins can use every import ability', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, CsvImport::factory()->create()))->toBeTrue();
})->with(['view', 'update']);

test('admins can open the import pages and upload files', function (string $ability) {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows($ability, CsvImport::class))->toBeTrue();
})->with(['viewAny', 'create']);

test('regular users cannot import anything', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, CsvImport::factory()->create()))->toBeTrue();
})->with(['view', 'update']);

test('regular users cannot open the import pages or upload files', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies($ability, CsvImport::class))->toBeTrue();
})->with(['viewAny', 'create']);
