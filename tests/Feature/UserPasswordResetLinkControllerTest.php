<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;

test('admins can send a password reset link to a user', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.password-reset-link', $user))
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('regular users are forbidden', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.password-reset-link', User::factory()->create()))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('a mail transport failure is reported instead of crashing', function () {
    Password::shouldReceive('sendResetLink')->once()->andThrow(new TransportException('Too many emails per second'));

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.password-reset-link', User::factory()->create()))
        ->assertRedirect();
});
