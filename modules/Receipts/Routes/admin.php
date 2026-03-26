<?php

use Illuminate\Support\Facades\Route;

Route::admin('receipts', function () {
    // Receipt CRUD
    Route::get('receipts', 'Receipts@index')->name('receipts.index');
    Route::get('receipts/upload', 'Receipts@upload')->name('receipts.upload');
    Route::post('receipts', 'Receipts@store')->name('receipts.store');
    Route::get('receipts/{id}/review', 'Receipts@review')->name('receipts.review');
    Route::put('receipts/{id}', 'Receipts@update')->name('receipts.update');
    Route::get('receipts/{id}/process', 'Receipts@process')->name('receipts.process');
    Route::post('receipts/{id}/process', 'Receipts@processStore')->name('receipts.process.store');
    Route::delete('receipts/{id}', 'Receipts@destroy')->name('receipts.destroy');

    // Bulk operations
    Route::get('receipts/bulk/upload', 'Receipts@bulkUpload')->name('receipts.bulk-upload');
    Route::post('receipts/bulk/store', 'Receipts@bulkStore')->name('receipts.bulk-store');
    Route::post('receipts/bulk/process', 'Receipts@bulkProcess')->name('receipts.bulk-process');

    // Settings
    Route::get('settings', 'Settings@index')->name('settings.index');
    Route::post('settings', 'Settings@update')->name('settings.update');
    Route::post('settings/rules', 'Settings@storeRule')->name('settings.rules.store');
    Route::delete('settings/rules/{id}', 'Settings@destroyRule')->name('settings.rules.destroy');
}, ['namespace' => 'Modules\Receipts\Http\Controllers']);
