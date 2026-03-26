<?php

use Illuminate\Support\Facades\Route;

Route::admin('budgets', function () {
    Route::post('budgets/{budget}/copy', 'Budgets@copy')->name('budgets.copy');
    Route::get('budgets/{budget}/report', 'BudgetReports@show')->name('budgets.report');
    Route::get('budgets/{budget}/report/export', 'BudgetReports@export')->name('budgets.report.export');
    Route::resource('budgets', 'Budgets');
}, ['namespace' => 'Modules\Budgets\Http\Controllers']);
