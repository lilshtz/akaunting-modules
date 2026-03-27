<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'double-entry' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('double-entry', function () {
    Route::resource('accounts', 'Accounts');
    Route::post('accounts/import', 'Accounts@import')->name('accounts.import');

    Route::resource('journals', 'Journals');

    Route::group(['prefix' => 'account-defaults', 'as' => 'account-defaults.'], function () {
        Route::get('/', 'AccountDefaults@index')->name('index');
        Route::post('/', 'AccountDefaults@update')->name('update');
    });

    // Reports
    Route::get('general-ledger', 'GeneralLedger@index')->name('general-ledger.index');
    Route::get('trial-balance', 'TrialBalance@index')->name('trial-balance.index');
    Route::get('balance-sheet', 'BalanceSheet@index')->name('balance-sheet.index');
    Route::get('profit-loss', 'ProfitLoss@index')->name('profit-loss.index');
});
