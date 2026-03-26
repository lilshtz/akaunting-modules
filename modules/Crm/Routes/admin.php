<?php

use Illuminate\Support\Facades\Route;

Route::admin('crm', function () {
    Route::get('contacts/import', 'Contacts@import')->name('contacts.import');
    Route::post('contacts/import', 'Contacts@importStore')->name('contacts.import.store');
    Route::post('contacts/{contact}/activities', 'Activities@store')->name('contacts.activities.store');
    Route::post('deals/{deal}/activities', 'Deals@storeActivity')->name('deals.activities.store');
    Route::post('deals/{deal}/move', 'Deals@move')->name('deals.move');
    Route::post('deals/{deal}/status', 'Deals@updateStatus')->name('deals.status');
    Route::post('pipeline-stages/reorder', 'PipelineStages@reorder')->name('pipeline-stages.reorder');
    Route::resource('deals', 'Deals');
    Route::resource('pipeline-stages', 'PipelineStages')->except(['show', 'create', 'edit']);
    Route::get('reports/deals', 'DealReports@index')->name('reports.deals');
    Route::resource('contacts', 'Contacts');
    Route::resource('companies', 'Companies');
    Route::resource('activities', 'Activities')->only(['index', 'destroy']);
}, ['namespace' => 'Modules\Crm\Http\Controllers']);
