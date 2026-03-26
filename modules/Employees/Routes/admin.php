<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'employees' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('employees', function () {
    Route::resource('departments', 'Departments');

    Route::post('employees/{employee}/documents', 'EmployeeDocuments@store')->name('employees.documents.store');
    Route::get('employees/{employee}/documents/{document}/download', 'EmployeeDocuments@download')->name('employees.documents.download');
    Route::delete('employees/{employee}/documents/{document}', 'EmployeeDocuments@destroy')->name('employees.documents.destroy');

    Route::resource('employees', 'Employees');
});
