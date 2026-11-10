<?php

use App\Http\Controllers\CsvImportBulkDestroyController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\Reports\IncompleteSplitReportController;
use App\Http\Controllers\Reports\RightsHistoryReportController;
use App\Http\Controllers\Reports\SongOwnershipReportController;
use App\Http\Controllers\Reports\WriterCatalogueReportController;
use App\Http\Controllers\UserBulkDestroyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordResetLinkController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkPartyController;
use App\Http\Controllers\WriterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::delete('users', UserBulkDestroyController::class)->name('users.destroy-many');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('users/{user}/password-reset-link', UserPasswordResetLinkController::class)->name('users.password-reset-link');

    Route::get('works/export', [WorkController::class, 'export'])->name('works.export');
    Route::resource('works', WorkController::class);

    Route::scopeBindings()->prefix('works/{work}/parties')->name('works.parties.')->group(function () {
        Route::post('/', [WorkPartyController::class, 'store'])->name('store');
        Route::put('{party}/change', [WorkPartyController::class, 'change'])->name('change');
        Route::put('{party}/correct', [WorkPartyController::class, 'correct'])->name('correct');
        Route::delete('{party}', [WorkPartyController::class, 'destroy'])->name('destroy');
    });

    Route::get('writers/export', [WriterController::class, 'export'])->name('writers.export');
    Route::resource('writers', WriterController::class);

    Route::get('publishers/export', [PublisherController::class, 'export'])->name('publishers.export');
    Route::resource('publishers', PublisherController::class);

    Route::prefix('imports')->name('imports.')->where(['type' => '[a-z]+'])->group(function () {
        Route::get('/', [CsvImportController::class, 'index'])->name('index');
        Route::delete('/', CsvImportBulkDestroyController::class)->name('destroy-many');
        Route::get('{type}', [CsvImportController::class, 'create'])->name('create');
        Route::post('{type}', [CsvImportController::class, 'store'])->name('store');
        Route::get('{type}/template', [CsvImportController::class, 'template'])->name('template');
        Route::get('{type}/example', [CsvImportController::class, 'example'])->name('example');

        Route::get('{csvImport}', [CsvImportController::class, 'show'])->whereNumber('csvImport')->name('show');
        Route::post('{csvImport}/confirm', [CsvImportController::class, 'confirm'])->whereNumber('csvImport')->name('confirm');
        Route::delete('{csvImport}', [CsvImportController::class, 'cancel'])->whereNumber('csvImport')->name('cancel');
        Route::delete('{csvImport}/record', [CsvImportController::class, 'destroy'])->whereNumber('csvImport')->name('destroy');
        Route::get('{csvImport}/file', [CsvImportController::class, 'download'])->whereNumber('csvImport')->name('download');
        Route::get('{csvImport}/errors', [CsvImportController::class, 'errors'])->whereNumber('csvImport')->name('errors');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('writer-catalogue', [WriterCatalogueReportController::class, 'index'])->name('writer-catalogue');
        Route::get('song-ownership', [SongOwnershipReportController::class, 'index'])->name('song-ownership');
        Route::get('rights-history', [RightsHistoryReportController::class, 'index'])->name('rights-history');
        Route::get('incomplete-split', [IncompleteSplitReportController::class, 'index'])->name('incomplete-split');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('writer-catalogue', [WriterCatalogueReportController::class, 'export'])->name('writer-catalogue');
            Route::get('song-ownership', [SongOwnershipReportController::class, 'export'])->name('song-ownership');
            Route::get('rights-history', [RightsHistoryReportController::class, 'export'])->name('rights-history');
            Route::get('incomplete-split', [IncompleteSplitReportController::class, 'export'])->name('incomplete-split');
        });
    });
});

require __DIR__.'/settings.php';
