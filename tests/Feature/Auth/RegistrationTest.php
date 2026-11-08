<?php

use App\Models\User;

test('the registration screen is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('people cannot register themselves', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
