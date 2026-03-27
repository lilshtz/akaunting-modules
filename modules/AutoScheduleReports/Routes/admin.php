<?php

use Illuminate\Support\Facades\Route;

Route::admin('auto-schedule-reports', function () {
    Route::post('schedules/{schedule}/toggle', 'Schedules@toggle')->name('schedules.toggle');
    Route::post('schedules/{schedule}/run', 'Schedules@runNow')->name('schedules.run');
    Route::get('runs/{run}/download', 'Schedules@downloadRun')->name('runs.download');
    Route::resource('schedules', 'Schedules');
}, ['namespace' => 'Modules\AutoScheduleReports\Http\Controllers']);
