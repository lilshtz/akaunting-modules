<?php

use Illuminate\Support\Facades\Route;

Route::admin('roles', function () {
    Route::get('roles', 'Roles@index')->name('roles.index');
    Route::get('roles/create', 'Roles@create')->name('roles.create');
    Route::post('roles', 'Roles@store')->name('roles.store');
    Route::get('roles/{id}/edit', 'Roles@edit')->name('roles.edit');
    Route::put('roles/{id}', 'Roles@update')->name('roles.update');
    Route::post('roles/{id}/duplicate', 'Roles@duplicate')->name('roles.duplicate');
    Route::delete('roles/{id}', 'Roles@destroy')->name('roles.destroy');

    Route::get('assignments', 'Assignments@index')->name('assignments.index');
    Route::post('assignments', 'Assignments@store')->name('assignments.store');
    Route::delete('assignments/{userId}', 'Assignments@destroy')->name('assignments.destroy');
}, ['namespace' => 'Modules\Roles\Http\Controllers']);
