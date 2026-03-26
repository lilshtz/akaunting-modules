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

    Route::get('general-ledger', 'GeneralLedger@index')->name('general-ledger.index');
    Route::get('general-ledger/export', 'GeneralLedger@export')->name('general-ledger.export');

    Route::get('trial-balance', 'TrialBalance@index')->name('trial-balance.index');
    Route::get('trial-balance/export', 'TrialBalance@export')->name('trial-balance.export');

    Route::get('balance-sheet', 'BalanceSheet@index')->name('balance-sheet.index');
    Route::get('balance-sheet/export', 'BalanceSheet@export')->name('balance-sheet.export');

    Route::get('profit-loss', 'ProfitLoss@index')->name('profit-loss.index');
    Route::get('profit-loss/export', 'ProfitLoss@export')->name('profit-loss.export');
});
