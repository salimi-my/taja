<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Alice Admin']);
});

describe('index', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('users.index'))
            ->assertForbidden();
    });

    test('admins see the first page of users newest first', function () {
        User::factory()->count(14)->create();

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/index')
                ->has('users.data', 10)
                ->where('users.meta.total', 15)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->has('roles', 2)
            );
    });

    test('users can be searched by name or email', function () {
        User::factory()->create(['name' => 'Zed Zebra', 'email' => 'zed@example.com']);
        User::factory()->create(['name' => 'Someone Else', 'email' => 'zebra-fan@example.com']);
        User::factory()->create(['name' => 'Unrelated', 'email' => 'unrelated@example.com']);

        $this->actingAs($this->admin)
            ->get(route('users.index', ['search' => 'zebra']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 2)
                ->where('filters.search', 'zebra')
            );
    });

    test('users can be filtered by role', function () {
        User::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('users.index', ['roles' => [UserRole::Admin->value]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.role', 'admin')
            );
    });

    test('users can be filtered by verification status', function () {
        User::factory()->count(2)->unverified()->create();

        $this->actingAs($this->admin)
            ->get(route('users.index', ['status' => ['unverified']]))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));

        $this->actingAs($this->admin)
            ->get(route('users.index', ['status' => ['verified']]))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
    });

    test('users can be filtered by the date they joined', function () {
        User::factory()->create(['created_at' => '2026-01-10']);
        User::factory()->create(['created_at' => '2026-02-10']);

        $this->actingAs($this->admin)
            ->get(route('users.index', ['start_date' => '2026-02-01', 'end_date' => '2026-02-28']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
    });

    test('users can be sorted by a column', function () {
        User::factory()->create(['name' => 'Aaron Aardvark']);

        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data.0.name', 'Aaron Aardvark')
                ->where('users.data.1.name', 'Alice Admin')
            );
    });

    test('the page size and page number are respected', function () {
        User::factory()->count(29)->create();

        $this->actingAs($this->admin)
            ->get(route('users.index', ['per_page' => 25, 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 5)
                ->where('users.meta.current_page', 2)
            );
    });

    test('users can be sorted in descending order', function () {
        User::factory()->create(['name' => 'Zed Zebra']);

        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertInertia(fn (Assert $page) => $page->where('users.data.0.name', 'Zed Zebra'));
    });

    test('every column shown in the table can be sorted', function (string $column) {
        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => $column, 'direction' => 'asc']))
            ->assertOk()
            ->assertSessionHasNoErrors();
    })->with(['name', 'email', 'role', 'email_verified_at', 'created_at']);

    test('rejects a column that cannot be sorted', function () {
        $this->actingAs($this->admin)
            ->get(route('users.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');
    });
});

describe('store', function () {
    test('admins can create a verified user', function () {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'New Person',
                'email' => 'new@example.com',
                'role' => 'admin',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'new@example.com')->firstOrFail();

        expect($user->name)->toBe('New Person')
            ->and($user->role)->toBe(UserRole::Admin)
            ->and($user->hasVerifiedEmail())->toBeTrue();
    });

    test('requires every field', function () {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    });

    test('rejects a duplicate email and an unknown role', function () {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Duplicate',
                'email' => $this->admin->email,
                'role' => 'superuser',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors(['email', 'role']);
    });

    test('regular users are forbidden', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('users.store'), [
                'name' => 'New Person',
                'email' => 'new@example.com',
                'role' => 'admin',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertForbidden();

        expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
    });
});

describe('update', function () {
    test('admins can update a user', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => 'Renamed',
                'email' => $user->email,
                'role' => 'admin',
            ])
            ->assertSessionHasNoErrors();

        expect($user->refresh()->name)->toBe('Renamed')
            ->and($user->role)->toBe(UserRole::Admin)
            ->and($user->hasVerifiedEmail())->toBeTrue();
    });

    test('keeps the existing password when none is provided', function () {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        expect($user->refresh()->password)->toBe($originalHash);
    });

    test('changes the password when one is provided', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue();
    });

    test('unverifies the email address when it changes', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => 'changed@example.com',
                'role' => 'user',
            ])
            ->assertSessionHasNoErrors();

        expect($user->refresh()->email_verified_at)->toBeNull();
    });

    test('admins cannot change their own role', function () {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'user',
            ])
            ->assertSessionHasErrors('role');

        expect($this->admin->refresh()->role)->toBe(UserRole::Admin);
    });

    test('regular users are forbidden', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('users.update', $user), [
                'name' => 'Promoted',
                'email' => $user->email,
                'role' => 'admin',
            ])
            ->assertForbidden();

        expect($user->refresh()->role)->toBe(UserRole::User);
    });
});

describe('destroy', function () {
    test('admins can delete a user', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect();

        expect($user->fresh())->toBeNull();
    });

    test('admins cannot delete themselves', function () {
        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->admin))
            ->assertForbidden();

        expect($this->admin->fresh())->not->toBeNull();
    });

    test('regular users are forbidden', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('users.destroy', User::factory()->create()))
            ->assertForbidden();
    });
});
