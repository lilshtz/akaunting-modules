<?php

use Illuminate\Support\Facades\Route;

Route::admin('crm', function () {
    Route::get('contacts/import', 'Contacts@import')->name('contacts.import');
    Route::post('contacts/import', 'Contacts@importStore')->name('contacts.import.store');
    Route::post('contacts/{contact}/activities', 'Activities@store')->name('contacts.activities.store');
    Route::resource('contacts', 'Contacts');
    Route::resource('companies', 'Companies');
    Route::resource('activities', 'Activities')->only(['index', 'destroy']);
}, ['namespace' => 'Modules\Crm\Http\Controllers']);
