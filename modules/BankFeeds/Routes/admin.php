<?php

use Illuminate\Support\Facades\Route;

Route::admin('bank-feeds', function () {
    // Imports
    Route::get('imports', 'Imports@index')->name('imports.index');
    Route::get('imports/create', 'Imports@create')->name('imports.create');
    Route::post('imports/upload', 'Imports@upload')->name('imports.upload');
    Route::post('imports/{id}/map-columns', 'Imports@mapColumns')->name('imports.map-columns');
    Route::delete('imports/{id}', 'Imports@destroy')->name('imports.destroy');

    // Transactions
    Route::get('transactions', 'Transactions@index')->name('transactions.index');
    Route::post('transactions/{id}/ignore', 'Transactions@ignore')->name('transactions.ignore');
    Route::post('transactions/bulk-categorize', 'Transactions@bulkCategorize')->name('transactions.bulk-categorize');

    // Matching
    Route::get('matching', 'Matching@index')->name('matching.index');
    Route::get('matching/{id}', 'Matching@show')->name('matching.show');
    Route::post('matching/{id}/accept', 'Matching@acceptMatch')->name('matching.accept');
    Route::post('matching/{id}/reject', 'Matching@rejectMatch')->name('matching.reject');
    Route::post('matching/{id}/create-transaction', 'Matching@createTransaction')->name('matching.create-transaction');
    Route::post('matching/auto-match', 'Matching@autoMatchAll')->name('matching.auto-match');
    Route::post('matching/bulk-ignore', 'Matching@bulkIgnore')->name('matching.bulk-ignore');

    // Reconciliation
    Route::get('reconciliation', 'Reconciliation@index')->name('reconciliation.index');
    Route::post('reconciliation', 'Reconciliation@create')->name('reconciliation.create');
    Route::get('reconciliation/{id}', 'Reconciliation@show')->name('reconciliation.show');
    Route::post('reconciliation/{id}/match', 'Reconciliation@matchTransaction')->name('reconciliation.match');
    Route::post('reconciliation/{id}/unmatch', 'Reconciliation@unmatchTransaction')->name('reconciliation.unmatch');
    Route::post('reconciliation/{id}/complete', 'Reconciliation@complete')->name('reconciliation.complete');
    Route::delete('reconciliation/{id}', 'Reconciliation@destroy')->name('reconciliation.destroy');

    // Rules
    Route::get('rules', 'Rules@index')->name('rules.index');
    Route::get('rules/create', 'Rules@create')->name('rules.create');
    Route::post('rules', 'Rules@store')->name('rules.store');
    Route::get('rules/{id}/edit', 'Rules@edit')->name('rules.edit');
    Route::put('rules/{id}', 'Rules@update')->name('rules.update');
    Route::delete('rules/{id}', 'Rules@destroy')->name('rules.destroy');
    Route::post('rules/reorder', 'Rules@reorder')->name('rules.reorder');

    // Settings
    Route::get('settings', 'Settings@index')->name('settings.index');
    Route::post('settings', 'Settings@update')->name('settings.update');
    Route::delete('settings/mappings/{accountId}', 'Settings@deleteMapping')->name('settings.delete-mapping');
    // Alias routes for Akaunting core compatibility (core expects bank-connections.*)
    Route::get('bank-connections', 'Imports@index')->name('bank-connections.index');
    Route::get('bank-connections/create', 'Imports@create')->name('bank-connections.create');

}, ['namespace' => 'Modules\BankFeeds\Http\Controllers']);
