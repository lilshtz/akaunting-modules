<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/payroll' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('payroll', function () {
    Route::get('payslips', 'Modules\Payroll\Http\Controllers\PortalPayslips@index')->name('payslips.index');
    Route::get('payslips/{id}', 'Modules\Payroll\Http\Controllers\PortalPayslips@show')->name('payslips.show');
    Route::get('payslips/{id}/download', 'Modules\Payroll\Http\Controllers\PortalPayslips@download')->name('payslips.download');
});
