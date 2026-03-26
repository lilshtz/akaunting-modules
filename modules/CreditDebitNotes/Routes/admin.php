<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'credit-debit-notes' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('credit-debit-notes', function () {
    // Credit note actions
    Route::post('credit-notes/{id}/send', 'CreditNotes@send')->name('credit-debit-notes.credit-notes.send');
    Route::post('credit-notes/{id}/mark-open', 'CreditNotes@markOpen')->name('credit-debit-notes.credit-notes.mark-open');
    Route::post('credit-notes/{id}/cancel', 'CreditNotes@cancel')->name('credit-debit-notes.credit-notes.cancel');
    Route::post('credit-notes/{id}/apply', 'CreditNotes@applyCredit')->name('credit-debit-notes.credit-notes.apply');
    Route::post('credit-notes/{id}/refund', 'CreditNotes@refund')->name('credit-debit-notes.credit-notes.refund');
    Route::post('credit-notes/{id}/convert', 'CreditNotes@convertToInvoice')->name('credit-debit-notes.credit-notes.convert');
    Route::get('credit-notes/{id}/pdf', 'CreditNotes@pdf')->name('credit-debit-notes.credit-notes.pdf');

    // Credit notes CRUD
    Route::resource('credit-notes', 'CreditNotes');

    // Debit note actions
    Route::post('debit-notes/{id}/send', 'DebitNotes@send')->name('credit-debit-notes.debit-notes.send');
    Route::post('debit-notes/{id}/mark-open', 'DebitNotes@markOpen')->name('credit-debit-notes.debit-notes.mark-open');
    Route::post('debit-notes/{id}/cancel', 'DebitNotes@cancel')->name('credit-debit-notes.debit-notes.cancel');
    Route::post('debit-notes/{id}/convert', 'DebitNotes@convertToBill')->name('credit-debit-notes.debit-notes.convert');
    Route::get('debit-notes/{id}/pdf', 'DebitNotes@pdf')->name('credit-debit-notes.debit-notes.pdf');

    // Debit notes CRUD
    Route::resource('debit-notes', 'DebitNotes');
});
