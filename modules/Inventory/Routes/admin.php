<?php

use Illuminate\Support\Facades\Route;

Route::admin('inventory', function () {
    Route::resource('warehouses', 'Warehouses');
    Route::resource('adjustments', 'Adjustments')->only(['index', 'store']);
    Route::resource('transfer-orders', 'TransferOrders')->except(['create', 'edit']);
    Route::post('transfer-orders/{id}/ship', 'TransferOrders@ship')->name('transfer-orders.ship');
    Route::post('transfer-orders/{id}/receive', 'TransferOrders@receive')->name('transfer-orders.receive');
    Route::resource('item-groups', 'ItemGroups')->except(['create', 'edit']);

    Route::get('stock', 'Stock@index')->name('stock.index');
    Route::put('stock', 'Stock@update')->name('stock.update');
    Route::get('stock/alerts', 'Stock@alerts')->name('stock.alerts');
    Route::get('items/{item}/stock', 'Stock@item')->name('stock.item');
    Route::resource('items.variants', 'Variants')->except(['create', 'edit']);
    Route::get('items/{item}/barcode', 'Barcodes@show')->name('barcodes.items.show');
    Route::get('items/{item}/variants/{variant}/barcode', 'Barcodes@show')->name('barcodes.variants.show');
    Route::get('items/{item}/barcode-labels', 'Barcodes@labels')->name('barcodes.labels');

    Route::get('reports/status', 'Reports@status')->name('reports.status');
    Route::get('reports/value', 'Reports@value')->name('reports.value');
    Route::get('reports/variants', 'Reports@variants')->name('reports.variants');
    Route::get('reports/adjustments', 'Reports@adjustments')->name('reports.adjustments');
    Route::get('reports/transfers', 'Reports@transfers')->name('reports.transfers');
    Route::get('reports/item-groups', 'Reports@itemGroups')->name('reports.item-groups');

    Route::get('history', 'History@index')->name('history.index');
}, ['namespace' => 'Modules\Inventory\Http\Controllers']);
