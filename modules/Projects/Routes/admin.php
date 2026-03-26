<?php

use Illuminate\Support\Facades\Route;

Route::admin('projects', function () {
    Route::get('projects/{project}/reports/pnl', 'ProjectReports@show')->name('projects.reports.pnl');
    Route::get('projects/{project}/transactions/create', 'Projects@createTransaction')->name('projects.transactions.create');
    Route::post('projects/{project}/transactions', 'Projects@storeTransaction')->name('projects.transactions.store');
    Route::delete('projects/{project}/transactions/{transaction}', 'Projects@destroyTransaction')->name('projects.transactions.destroy');
    Route::post('projects/{project}/members', 'Projects@updateMembers')->name('projects.members.update');
    Route::post('projects/{project}/timesheets', 'Timesheets@store')->name('projects.timesheets.store');

    Route::post('projects/{project}/milestones/{milestone}/complete', 'Milestones@complete')->name('projects.milestones.complete');
    Route::resource('projects.milestones', 'Milestones')->only(['store', 'edit', 'update', 'destroy'])->shallow();

    Route::post('projects/{project}/tasks/{task}/transition', 'Tasks@transition')->name('projects.tasks.transition');
    Route::post('projects/{project}/tasks/{task}/timer/start', 'Timesheets@start')->name('projects.tasks.timer.start');
    Route::post('projects/{project}/tasks/{task}/timer/stop', 'Timesheets@stop')->name('projects.tasks.timer.stop');
    Route::resource('projects.tasks', 'Tasks')->only(['store', 'edit', 'update', 'destroy'])->shallow();

    Route::resource('projects.discussions', 'Discussions')->only(['store', 'update', 'destroy'])->shallow();
    Route::resource('projects', 'Projects');
}, ['namespace' => 'Modules\Projects\Http\Controllers']);
