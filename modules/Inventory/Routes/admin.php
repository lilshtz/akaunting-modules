<?php

use Illuminate\Support\Facades\Route;

Route::admin('inventory', function () {
    Route::resource('warehouses', 'Warehouses');

    Route::get('stock', 'Stock@index')->name('stock.index');
    Route::put('stock', 'Stock@update')->name('stock.update');
    Route::get('stock/alerts', 'Stock@alerts')->name('stock.alerts');
    Route::get('items/{item}/stock', 'Stock@item')->name('stock.item');

    Route::get('reports/status', 'Reports@status')->name('reports.status');
    Route::get('reports/value', 'Reports@value')->name('reports.value');

    Route::get('history', 'History@index')->name('history.index');
}, ['namespace' => 'Modules\Inventory\Http\Controllers']);
