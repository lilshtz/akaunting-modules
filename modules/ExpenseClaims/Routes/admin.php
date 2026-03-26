<?php

use Illuminate\Support\Facades\Route;

Route::admin('expense-claims', function () {
    Route::get('claims/export', 'Claims@export')->name('claims.export');
    Route::get('claims/import', 'Claims@import')->name('claims.import');
    Route::post('claims/import', 'Claims@importStore')->name('claims.import.store');
    Route::get('claims/{claim}/pdf', 'Claims@pdf')->name('claims.pdf');
    Route::post('claims/{claim}/submit', 'Claims@submit')->name('claims.submit');
    Route::post('claims/{claim}/approve', 'Claims@approve')->name('claims.approve');
    Route::post('claims/{claim}/refuse', 'Claims@refuse')->name('claims.refuse');
    Route::post('claims/{claim}/pay', 'Claims@pay')->name('claims.pay');
    Route::resource('claims', 'Claims');

    Route::resource('categories', 'Categories')->except(['show']);

    Route::get('reports', 'ClaimReports@index')->name('reports.index');
    Route::get('reports/export', 'ClaimReports@export')->name('reports.export');
});
