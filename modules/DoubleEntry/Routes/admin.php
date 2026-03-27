<?php

use Illuminate\Support\Facades\Route;

Route::admin('double-entry', function () {
    Route::get('accounts/import', 'Accounts@import')->name('accounts.import');
    Route::post('accounts/import', 'Accounts@storeImport')->name('accounts.store-import');
    Route::post('accounts/seed', 'Accounts@seed')->name('accounts.seed');
    Route::resource('accounts', 'Accounts')->except(['show']);

    Route::post('journals/{journal}/post', 'Journals@post')->name('journals.post');
    Route::post('journals/{journal}/void', 'Journals@void')->name('journals.void');
    Route::resource('journals', 'Journals');

    Route::get('account-defaults', 'AccountDefaults@index')->name('account-defaults.index');
    Route::post('account-defaults', 'AccountDefaults@update')->name('account-defaults.update');

    Route::get('reports/general-ledger', 'GeneralLedger@index')->name('general-ledger.index');
    Route::get('reports/trial-balance', 'TrialBalance@index')->name('trial-balance.index');
    Route::get('reports/balance-sheet', 'BalanceSheet@index')->name('balance-sheet.index');
    Route::get('reports/profit-loss', 'ProfitLoss@index')->name('profit-loss.index');
});
