<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'stripe' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('stripe', function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('/', 'Settings@update')->name('update');
    });

    Route::group(['prefix' => 'payments', 'as' => 'payments.'], function () {
        Route::get('/', 'PaymentHistory@index')->name('index');
        Route::post('{payment}/refund', 'PaymentHistory@refund')->name('refund');
    });
});
