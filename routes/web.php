<?php

use App\Http\Controllers\AttendanceSheetController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('stranky/{page}', PageController::class)->name('pages.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('kurzy', 'pages::courses.index')->name('courses.index');
    Route::livewire('moje-kurzy', 'pages::courses.mine')->name('courses.mine');

    Route::get('certifikaty/{registration}', [CertificateController::class, 'show'])->name('certificates.show');

    Route::middleware('can:admin')->prefix('admin/kurzy')->name('admin.courses.')->group(function () {
        Route::livewire('novy', 'pages::admin.courses.form')->name('create');
        Route::livewire('{course}', 'pages::admin.courses.show')->name('show');
        Route::livewire('{course}/upravit', 'pages::admin.courses.form')->name('edit');
        Route::get('{course}/certifikaty', [CertificateController::class, 'course'])->name('certificates');
        Route::get('{course}/prezencni-listina', AttendanceSheetController::class)->name('attendance-sheet');
    });

    Route::middleware('can:admin')->group(function () {
        Route::livewire('admin/kategorie', 'pages::admin.categories.index')->name('admin.categories.index');
    });
});

require __DIR__.'/settings.php';
