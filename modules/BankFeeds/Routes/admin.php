<?php

use Illuminate\Support\Facades\Route;

Route::admin('bank-feeds', function () {
    Route::get('imports', 'Imports@index')->name('imports.index');
    Route::get('imports/create', 'Imports@create')->name('imports.create');
    Route::post('imports/upload', 'Imports@upload')->name('imports.upload');
    Route::get('imports/{id}/map', 'Imports@mapColumns')->name('imports.map');
    Route::post('imports/{id}/process', 'Imports@process')->name('imports.process');
    Route::delete('imports/{id}', 'Imports@destroy')->name('imports.destroy');

    Route::get('transactions', 'Transactions@index')->name('transactions.index');
    Route::patch('transactions/{id}/ignore', 'Transactions@ignore')->name('transactions.ignore');
    Route::post('transactions/bulk-ignore', 'Transactions@bulkIgnore')->name('transactions.bulk-ignore');

    Route::resource('rules', 'Rules');
    Route::post('rules/apply', 'Rules@apply')->name('rules.apply');

    Route::get('matching', 'Matching@index')->name('matching.index');
    Route::post('matching/auto-match', 'Matching@autoMatch')->name('matching.auto-match');
    Route::post('matching/bulk-ignore', 'Matching@bulkIgnore')->name('matching.bulk-ignore');
    Route::get('matching/{id}', 'Matching@show')->name('matching.show');
    Route::post('matching/{id}/accept', 'Matching@accept')->name('matching.accept');
    Route::post('matching/{id}/reject', 'Matching@reject')->name('matching.reject');
    Route::post('matching/{id}/create-journal', 'Matching@createJournal')->name('matching.create-journal');

    Route::get('reconciliation', 'Reconciliation@index')->name('reconciliation.index');
    Route::get('reconciliation/create', 'Reconciliation@create')->name('reconciliation.create');
    Route::post('reconciliation', 'Reconciliation@store')->name('reconciliation.store');
    Route::get('reconciliation/{id}', 'Reconciliation@show')->name('reconciliation.show');
    Route::post('reconciliation/{id}/complete', 'Reconciliation@complete')->name('reconciliation.complete');
}, ['namespace' => 'Modules\BankFeeds\Http\Controllers']);
