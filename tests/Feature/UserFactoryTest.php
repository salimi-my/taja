<?php

use App\Enums\Gender;
use App\Models\User;
use App\Rules\InternationalPhone;
use Illuminate\Support\Facades\Validator;

test('the user factory gives every user a gender and a valid Malaysian mobile number', function () {
    $users = User::factory()->count(20)->create();

    foreach ($users as $user) {
        expect($user->gender)->toBeInstanceOf(Gender::class)
            ->and($user->phone)->toStartWith('+601')
            ->and(Validator::make(['phone' => $user->phone], ['phone' => [new InternationalPhone]])->passes())->toBeTrue();
    }
});
