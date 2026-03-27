<?php

use Illuminate\Support\Facades\Route;

Route::admin('appointments', function () {
    Route::get('/', 'Appointments@index')->name('index');
    Route::get('calendar', 'Appointments@index')->name('calendar');
    Route::get('create', 'Appointments@create')->name('create');
    Route::post('/', 'Appointments@store')->name('store');
    Route::get('send-reminders', 'Appointments@sendReminders')->name('reminders.send');
    Route::get('{id}', 'Appointments@show')->name('show');
    Route::get('{id}/edit', 'Appointments@edit')->name('edit');
    Route::put('{id}', 'Appointments@update')->name('update');
    Route::delete('{id}', 'Appointments@destroy')->name('destroy');

    Route::prefix('forms')->as('forms.')->group(function () {
        Route::get('/', 'AppointmentForms@index')->name('index');
        Route::get('create', 'AppointmentForms@create')->name('create');
        Route::post('/', 'AppointmentForms@store')->name('store');
        Route::get('{id}', 'AppointmentForms@show')->name('show');
        Route::get('{id}/edit', 'AppointmentForms@edit')->name('edit');
        Route::put('{id}', 'AppointmentForms@update')->name('update');
        Route::delete('{id}', 'AppointmentForms@destroy')->name('destroy');
    });

    Route::prefix('leave')->as('leave.')->group(function () {
        Route::get('/', 'LeaveRequests@index')->name('index');
        Route::get('create', 'LeaveRequests@create')->name('create');
        Route::post('/', 'LeaveRequests@store')->name('store');
        Route::get('{id}', 'LeaveRequests@show')->name('show');
        Route::get('{id}/edit', 'LeaveRequests@edit')->name('edit');
        Route::put('{id}', 'LeaveRequests@update')->name('update');
        Route::delete('{id}', 'LeaveRequests@destroy')->name('destroy');
        Route::post('{id}/approve', 'LeaveRequests@approve')->name('approve');
        Route::post('{id}/refuse', 'LeaveRequests@refuse')->name('refuse');
    });

    Route::prefix('reports')->as('reports.')->group(function () {
        Route::get('/', 'Reports@index')->name('index');
        Route::get('export', 'Reports@export')->name('export');
    });

    Route::prefix('settings')->as('settings.')->group(function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::put('/', 'Settings@update')->name('update');
    });
});
