<?php

use Illuminate\Support\Facades\Route;

/**
 * 'signed' middleware and 'signed/stripe' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('stripe', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
    Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
    Route::get('invoices/{invoice}/success', 'Payment@success')->name('invoices.success');
});
