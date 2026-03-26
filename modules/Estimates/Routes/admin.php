<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'estimates' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('estimates', function () {
    // Estimate settings
    Route::match(['get', 'post'], 'settings', 'Estimates@settings')->name('estimates.settings');

    // Estimate actions
    Route::post('estimates/{estimate}/send', 'Estimates@send')->name('estimates.estimates.send');
    Route::post('estimates/{estimate}/approve', 'Estimates@approve')->name('estimates.estimates.approve');
    Route::post('estimates/{estimate}/refuse', 'Estimates@refuse')->name('estimates.estimates.refuse');
    Route::post('estimates/{estimate}/convert', 'Estimates@convert')->name('estimates.estimates.convert');
    Route::post('estimates/{estimate}/duplicate', 'Estimates@duplicate')->name('estimates.estimates.duplicate');
    Route::get('estimates/{estimate}/pdf', 'Estimates@pdf')->name('estimates.estimates.pdf');

    // CRUD
    Route::resource('estimates', 'Estimates');
});
