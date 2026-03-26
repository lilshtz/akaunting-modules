<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'paypal-sync' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('paypal-sync', function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('/', 'Settings@update')->name('update');
    });

    Route::group(['prefix' => 'transactions', 'as' => 'transactions.'], function () {
        Route::get('/', 'Transactions@index')->name('index');
        Route::post('sync', 'Transactions@sync')->name('sync');
        Route::post('{transaction}/match', 'Transactions@match')->name('match');
    });
});
