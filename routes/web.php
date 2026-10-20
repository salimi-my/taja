<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserBulkDestroyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::delete('users', UserBulkDestroyController::class)->name('users.destroy-many');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('users/{user}/password-reset-link', UserPasswordResetLinkController::class)->name('users.password-reset-link');

    /*
     * Dummy pages that demonstrate sidebar sub menus. Replace these with real
     * controllers and pages when you build the Content section.
     */
    Route::inertia('posts', 'content/placeholder', [
        'title' => 'All Posts',
        'description' => 'Every post you have written will be listed here.',
    ])->name('posts.index');
    Route::inertia('posts/create', 'content/placeholder', [
        'title' => 'New Post',
        'description' => 'The post editor will live here.',
    ])->name('posts.create');
    Route::inertia('categories', 'content/placeholder', [
        'title' => 'Categories',
        'description' => 'Organize your posts into categories.',
    ])->name('categories.index');

    Route::inertia('media', 'content/placeholder', [
        'title' => 'Media Library',
        'description' => 'Browse the images and files you have uploaded.',
    ])->name('media.index');
    Route::inertia('media/upload', 'content/placeholder', [
        'title' => 'Upload Media',
        'description' => 'Upload new images and files here.',
    ])->name('media.upload');

    Route::inertia('comments', 'content/placeholder', [
        'title' => 'All Comments',
        'description' => 'Every comment left on your posts.',
    ])->name('comments.index');
    Route::inertia('comments/pending', 'content/placeholder', [
        'title' => 'Pending Comments',
        'description' => 'Comments waiting for moderation.',
    ])->name('comments.pending');
});

require __DIR__.'/settings.php';
