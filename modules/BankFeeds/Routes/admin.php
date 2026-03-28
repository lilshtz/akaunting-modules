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
}, ['namespace' => 'Modules\BankFeeds\Http\Controllers']);
