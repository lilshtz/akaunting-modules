<?php

use Illuminate\Support\Facades\Route;

/**
 * Public portal routes (no auth required)
 */
Route::prefix('estimates/portal')->name('estimates.portal.')->group(function () {
    Route::get('{token}', 'Modules\Estimates\Http\Controllers\Portal@show')->name('show');
    Route::post('{token}/approve', 'Modules\Estimates\Http\Controllers\Portal@approve')->name('approve');
    Route::post('{token}/refuse', 'Modules\Estimates\Http\Controllers\Portal@refuse')->name('refuse');
});
