<?php

use Illuminate\Support\Facades\Route;

Route::admin('pos', function () {
    Route::get('/', 'Orders@index')->name('orders.index');
    Route::post('orders', 'Orders@store')->name('orders.store');
    Route::get('orders/history', 'Orders@history')->name('orders.history');
    Route::get('orders/{order}', 'Orders@show')->name('orders.show');
    Route::post('orders/{order}/refund', 'Orders@refund')->name('orders.refund');
    Route::get('orders/{order}/receipt', 'Orders@receipt')->name('orders.receipt');

    Route::get('reports/daily', 'Reports@daily')->name('reports.daily');

    Route::get('settings', 'Settings@edit')->name('settings.edit');
    Route::put('settings', 'Settings@update')->name('settings.update');
}, ['namespace' => 'Modules\Pos\Http\Controllers']);
