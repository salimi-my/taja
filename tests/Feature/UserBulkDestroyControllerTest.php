<?php

use App\Models\User;

test('admins can delete several users at once', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy-many'), ['ids' => $users->pluck('id')->all()])
        ->assertRedirect();

    expect(User::whereIn('id', $users->pluck('id'))->exists())->toBeFalse();
});

test('skips the current user when they are in the selection', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy-many'), ['ids' => [$admin->id, $other->id]])
        ->assertRedirect();

    expect($admin->fresh())->not->toBeNull()
        ->and($other->fresh())->toBeNull();
});

test('requires at least one existing user id', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy-many'), [])
        ->assertSessionHasErrors('ids');

    $this->actingAs($admin)
        ->delete(route('users.destroy-many'), ['ids' => [999999]])
        ->assertSessionHasErrors('ids.0');
});

test('regular users are forbidden', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('users.destroy-many'), ['ids' => [$other->id]])
        ->assertForbidden();

    expect($other->fresh())->not->toBeNull();
});
