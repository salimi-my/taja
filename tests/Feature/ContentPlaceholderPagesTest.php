<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page from the content pages', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'posts.index',
    'posts.create',
    'categories.index',
    'media.index',
    'media.upload',
    'comments.index',
    'comments.pending',
]);

test('authenticated users can open the content pages', function (string $routeName, string $title) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertInertia(fn (Assert $page) => $page
            ->component('content/placeholder')
            ->where('title', $title)
            ->has('description')
        );
})->with([
    ['posts.index', 'All Posts'],
    ['posts.create', 'New Post'],
    ['categories.index', 'Categories'],
    ['media.index', 'Media Library'],
    ['media.upload', 'Upload Media'],
    ['comments.index', 'All Comments'],
    ['comments.pending', 'Pending Comments'],
]);
