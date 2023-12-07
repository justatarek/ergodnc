<?php

use App\Http\Controllers\Api\HostReservationController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\OfficeImageController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VisitorReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::name('api.')->group(function () {
    // Tags
    Route::get('tags', TagController::class)->name('tags.index');

    // Offices
    Route::apiResource('offices', OfficeController::class);

    // Office Images
    Route::prefix('offices/{office}/images')
        ->name('offices.images.')
        ->controller(OfficeImageController::class)
        ->middleware(['auth:sanctum', 'verified'])
        ->scopeBindings()
        ->group(function () {
            Route::post('/', 'store')->name('store');
            Route::delete('{image}', 'destroy')->name('destroy');
        });

    // Reservations
    Route::prefix('reservations')->name('reservations.')->middleware(['auth:sanctum', 'verified'])->group(function () {
        // Host Reservations
        Route::get('host', HostReservationController::class)->name('host.index');

        // Visitor Reservations
        Route::name('visitor.')->controller(VisitorReservationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::delete('{reservation}', 'destroy')->name('destroy');
        });
    });
});
