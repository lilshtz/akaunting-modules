<?php

use Illuminate\Support\Facades\Route;

Route::admin('double-entry', function () {
    Route::get('accounts/import', 'Accounts@import')->name('accounts.import');
    Route::post('accounts/import', 'Accounts@importProcess')->name('accounts.import.process');
    Route::patch('accounts/{account}/toggle', 'Accounts@toggle')->name('accounts.toggle');
    Route::resource('accounts', 'Accounts')->except('show');
    Route::resource('journals', 'Journals');
    Route::get('general-ledger', 'GeneralLedger@index')->name('general-ledger.index');
    Route::get('trial-balance', 'TrialBalance@index')->name('trial-balance.index');
    Route::get('balance-sheet', 'BalanceSheet@index')->name('balance-sheet.index');
    Route::get('profit-loss', 'ProfitLoss@index')->name('profit-loss.index');
    Route::get('account-defaults', 'AccountDefaults@index')->name('account-defaults.index');
    Route::post('account-defaults', 'AccountDefaults@store')->name('account-defaults.store');
}, ['namespace' => 'Modules\DoubleEntry\Http\Controllers']);
