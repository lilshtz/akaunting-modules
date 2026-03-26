<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'double-entry' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('double-entry', function () {
    Route::get('accounts/import', 'Accounts@import')->name('accounts.import');
    Route::post('accounts/import', 'Accounts@importProcess')->name('accounts.import.process');
    Route::resource('accounts', 'Accounts');

    Route::post('journals/{id}/duplicate', 'Journals@duplicate')->name('journals.duplicate');
    Route::resource('journals', 'Journals');
});
