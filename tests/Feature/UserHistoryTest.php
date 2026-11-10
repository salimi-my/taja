<?php

use App\Actions\Users\SummarizeUserHistory;
use App\Enums\UserChangeType;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Fake Admin']);
});

describe('admin changes', function () {
    test('creating a user logs a created entry without secrets', function () {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'New Person',
                'email' => 'new@example.com',
                'phone' => '+60110000003',
                'gender' => 'female',
                'role' => 'user',
            ])
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::Created)
            ->and($entry->subject_id)->toBe($user->id)
            ->and($entry->subject_name)->toBe('New Person')
            ->and($entry->subject_email)->toBe('new@example.com')
            ->and($entry->actor_id)->toBe($this->admin->id)
            ->and($entry->actor_name)->toBe('Fake Admin')
            ->and($entry->new_snapshot)->toMatchArray(['name' => 'New Person', 'role' => 'user', 'gender' => 'female', 'email_verified' => false])
            ->and($entry->new_snapshot)->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret'])
            ->and(json_encode($entry->new_snapshot))->not->toContain($user->password);
    });

    test('updating a user logs only what changed', function () {
        $user = User::factory()->create(['name' => 'Old Name', 'phone' => '+60110000003']);

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
                'phone' => '+60110000003',
                'gender' => $user->gender?->value,
                'role' => 'user',
            ])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::Updated)
            ->and($entry->previous_snapshot)->toBe(['name' => 'Old Name'])
            ->and($entry->new_snapshot)->toBe(['name' => 'New Name'])
            ->and($entry->actor_id)->toBe($this->admin->id);
    });

    test('saving a user without changes logs nothing', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender?->value,
                'role' => $user->role->value,
            ])
            ->assertSessionHasNoErrors();

        expect(UserHistory::count())->toBe(0);
    });

    test('changing only the role logs a role change', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender?->value,
                'role' => 'admin',
            ])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::RoleChanged)
            ->and($entry->previous_snapshot)->toBe(['role' => 'user'])
            ->and($entry->new_snapshot)->toBe(['role' => 'admin'])
            ->and(app(SummarizeUserHistory::class)($entry))->toBe('User role changed: role from user to admin.');
    });

    test('changing the email also logs that it is unverified again', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => 'changed@example.com',
                'phone' => $user->phone,
                'gender' => $user->gender?->value,
                'role' => $user->role->value,
            ])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::Updated)
            ->and($entry->new_snapshot)->toBe(['email' => 'changed@example.com', 'email_verified' => false])
            ->and($entry->subject_email)->toBe('changed@example.com');
    });

    test('deleting a user keeps an entry that still names the user and the actor', function () {
        $user = User::factory()->create(['name' => 'Gone Person', 'email' => 'gone@example.com']);

        $this->actingAs($this->admin)->delete(route('users.destroy', $user))->assertRedirect();

        $entry = UserHistory::sole();

        expect(User::find($user->id))->toBeNull()
            ->and($entry->change_type)->toBe(UserChangeType::Deleted)
            ->and($entry->subject_id)->toBe($user->id)
            ->and($entry->subject_name)->toBe('Gone Person')
            ->and($entry->subject_email)->toBe('gone@example.com')
            ->and($entry->previous_snapshot)->toMatchArray(['name' => 'Gone Person', 'email' => 'gone@example.com'])
            ->and($entry->actor_name)->toBe('Fake Admin');
    });

    test('deleting several users logs one entry for each user that was deleted', function () {
        $users = User::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy-many'), ['ids' => [$this->admin->id, ...$users->pluck('id')->all()]])
            ->assertRedirect();

        expect(UserHistory::where('change_type', UserChangeType::Deleted)->pluck('subject_id')->all())
            ->toEqualCanonicalizing($users->pluck('id')->all());
    });

    test('the actor name stays after the actor is deleted', function () {
        $user = User::factory()->create();
        UserHistory::factory()->forSubject($user)->byActor($this->admin)->create();

        $this->admin->delete();

        $entry = UserHistory::sole();

        expect($entry->actor_id)->toBeNull()
            ->and($entry->actor_name)->toBe('Fake Admin');
    });
});

describe('own account', function () {
    test('updating the profile logs the user as the actor', function () {
        $user = User::factory()->create(['name' => 'Before Name']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'After Name', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::Updated)
            ->and($entry->new_snapshot)->toMatchArray(['name' => 'After Name'])
            ->and($entry->actor_id)->toBe($user->id);
    });

    test('deleting the own account keeps an entry that names the user', function () {
        $user = User::factory()->create(['name' => 'Leaving Person']);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect(User::find($user->id))->toBeNull()
            ->and($entry->change_type)->toBe(UserChangeType::Deleted)
            ->and($entry->actor_name)->toBe('Leaving Person');
    });

    test('uploading and removing an avatar log an avatar change without the file path', function () {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('avatar.png')])
            ->assertSessionHasNoErrors();

        $this->delete(route('profile.avatar.destroy'))->assertRedirect();

        $entries = UserHistory::orderBy('id')->get();

        expect($entries)->toHaveCount(2)
            ->and($entries[0]->change_type)->toBe(UserChangeType::AvatarChanged)
            ->and($entries[0]->new_snapshot)->toBe(['has_avatar' => true])
            ->and(app(SummarizeUserHistory::class)($entries[0]))->toBe('Avatar uploaded.')
            ->and($entries[1]->new_snapshot)->toBe(['has_avatar' => false])
            ->and(app(SummarizeUserHistory::class)($entries[1]))->toBe('Avatar removed.');
    });

    test('removing an avatar that does not exist logs nothing', function () {
        $user = User::factory()->create(['avatar' => null]);

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();

        expect(UserHistory::count())->toBe(0);
    });

    test('changing the password logs an event without any value', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'a-new-password-1',
                'password_confirmation' => 'a-new-password-1',
            ])
            ->assertSessionHasNoErrors();

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::PasswordChanged)
            ->and($entry->previous_snapshot)->toBeNull()
            ->and($entry->new_snapshot)->toBeNull()
            ->and($entry->actor_id)->toBe($user->id);
    });
});

describe('sign-in flows', function () {
    test('resetting a forgotten password logs the reset and the verified email', function () {
        $this->skipUnlessFortifyHas(Features::resetPasswords());
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'a-new-password-1',
                'password_confirmation' => 'a-new-password-1',
            ])->assertSessionHasNoErrors();

            return true;
        });

        expect(UserHistory::orderBy('id')->pluck('change_type')->all())
            ->toBe([UserChangeType::PasswordReset, UserChangeType::EmailVerified]);
    });

    test('verifying the email address from the emailed link logs it once', function () {
        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::EmailVerified)
            ->and($entry->new_snapshot)->toBe(['email_verified' => true]);
    });

    test('two-factor authentication counts as enabled once the code is confirmed', function () {
        Features::twoFactorAuthentication(['confirm' => true]);
        $user = User::factory()->create();

        event(new TwoFactorAuthenticationEnabled($user));

        expect(UserHistory::count())->toBe(0);

        event(new TwoFactorAuthenticationConfirmed($user));

        expect(UserHistory::sole()->change_type)->toBe(UserChangeType::TwoFactorEnabled);
    });

    test('two-factor authentication counts as enabled right away without confirmation', function () {
        Features::twoFactorAuthentication(['confirm' => false]);
        $user = User::factory()->create();

        event(new TwoFactorAuthenticationEnabled($user));

        expect(UserHistory::sole()->change_type)->toBe(UserChangeType::TwoFactorEnabled);
    });

    test('turning off two-factor authentication is logged with the signed-in user as actor', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        event(new TwoFactorAuthenticationDisabled($user));

        $entry = UserHistory::sole();

        expect($entry->change_type)->toBe(UserChangeType::TwoFactorDisabled)
            ->and($entry->actor_id)->toBe($user->id);
    });
});

test('entries are insert-only', function () {
    $entry = UserHistory::factory()->create();

    expect(fn () => $entry->update(['remarks' => 'Edited']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});
