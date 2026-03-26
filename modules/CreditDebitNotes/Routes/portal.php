<?php

use Illuminate\Support\Facades\Route;

/**
 * Public portal routes (no auth required)
 */
Route::prefix('credit-debit-notes/portal')->name('credit-debit-notes.portal.')->group(function () {
    Route::get('{token}', 'Modules\CreditDebitNotes\Http\Controllers\Portal@show')->name('show');
});
