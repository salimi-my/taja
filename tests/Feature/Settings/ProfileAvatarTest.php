<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('an avatar can be uploaded', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($user->refresh()->avatar)->not->toBeNull()
        ->and($user->avatar_url)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar);
});

test('uploading a new avatar deletes the previous file', function () {
    $user = User::factory()->create();
    $previousPath = UploadedFile::fake()->image('old.png')->store('avatars', 'public');
    $user->update(['avatar' => $previousPath]);

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('new.png'),
        ])
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($previousPath);
    Storage::disk('public')->assertExists($user->refresh()->avatar);
});

test('only raster images are accepted as avatars', function (string $filename, string $mimeType) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create($filename, 10, $mimeType),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar)->toBeNull();
})->with([
    'svg' => ['avatar.svg', 'image/svg+xml'],
    'pdf' => ['avatar.pdf', 'application/pdf'],
]);

test('an avatar is required', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('profile.avatar.update'))
        ->assertSessionHasErrors('avatar');
});

test('an avatar can be removed', function () {
    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('avatar.png')->store('avatars', 'public');
    $user->update(['avatar' => $path]);

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect();

    expect($user->refresh()->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('guests cannot change avatars', function () {
    $this->post(route('profile.avatar.update'))->assertRedirect(route('login'));
    $this->delete(route('profile.avatar.destroy'))->assertRedirect(route('login'));
});
