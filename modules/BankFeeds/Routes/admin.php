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
}, ['namespace' => 'Modules\BankFeeds\Http\Controllers']);
