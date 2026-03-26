<?php

use Illuminate\Support\Facades\Route;

Route::admin('payroll', function () {
    Route::resource('pay-items', 'PayItems')->except(['show']);
    Route::resource('pay-calendars', 'PayCalendars');

    Route::get('runs/create', 'PayrollRuns@create')->name('payroll-runs.create');
    Route::post('runs', 'PayrollRuns@store')->name('payroll-runs.store');
    Route::get('runs', 'PayrollRuns@index')->name('payroll-runs.index');
    Route::get('runs/{id}', 'PayrollRuns@show')->name('payroll-runs.show');
    Route::put('runs/{id}', 'PayrollRuns@update')->name('payroll-runs.update');
    Route::post('runs/{id}/approve', 'PayrollRuns@approve')->name('payroll-runs.approve');
    Route::post('runs/{id}/process', 'PayrollRuns@process')->name('payroll-runs.process');
    Route::post('runs/{id}/payslips/email', 'Payslips@bulkEmail')->name('payroll-runs.payslips.email');

    Route::get('payslips/{id}', 'Payslips@show')->name('payslips.show');
    Route::get('payslips/{id}/download', 'Payslips@download')->name('payslips.download');
    Route::post('payslips/{id}/email', 'Payslips@email')->name('payslips.email');
    Route::get('employees/{employeeId}/payslips', 'Payslips@history')->name('employees.payslips.history');

    Route::get('settings', 'Settings@index')->name('settings.index');
    Route::post('settings', 'Settings@update')->name('settings.update');
}, ['namespace' => 'Modules\Payroll\Http\Controllers']);
