<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/inventory')->group(function () {
    Route::get('warehouses', [\Modules\Inventory\Http\Controllers\Warehouses::class, 'index']);
    Route::get('stock', [\Modules\Inventory\Http\Controllers\Stock::class, 'index']);
    Route::get('stock/alerts', [\Modules\Inventory\Http\Controllers\Stock::class, 'alerts']);
    Route::put('stock', [\Modules\Inventory\Http\Controllers\Stock::class, 'update']);
    Route::get('items/{item}/stock', [\Modules\Inventory\Http\Controllers\Stock::class, 'item']);
    Route::get('items/{item}/variants', [\Modules\Inventory\Http\Controllers\Variants::class, 'index']);
    Route::post('items/{item}/variants', [\Modules\Inventory\Http\Controllers\Variants::class, 'store']);
    Route::get('items/{item}/variants/{variant}', [\Modules\Inventory\Http\Controllers\Variants::class, 'show']);
    Route::put('items/{item}/variants/{variant}', [\Modules\Inventory\Http\Controllers\Variants::class, 'update']);
    Route::delete('items/{item}/variants/{variant}', [\Modules\Inventory\Http\Controllers\Variants::class, 'destroy']);
    Route::get('items/{item}/barcode', [\Modules\Inventory\Http\Controllers\Barcodes::class, 'show']);
    Route::get('items/{item}/variants/{variant}/barcode', [\Modules\Inventory\Http\Controllers\Barcodes::class, 'show']);
    Route::get('items/{item}/barcode-labels', [\Modules\Inventory\Http\Controllers\Barcodes::class, 'labels']);
    Route::get('adjustments', [\Modules\Inventory\Http\Controllers\Adjustments::class, 'index']);
    Route::post('adjustments', [\Modules\Inventory\Http\Controllers\Adjustments::class, 'store']);
    Route::get('transfer-orders', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'index']);
    Route::post('transfer-orders', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'store']);
    Route::get('transfer-orders/{id}', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'show']);
    Route::put('transfer-orders/{id}', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'update']);
    Route::delete('transfer-orders/{id}', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'destroy']);
    Route::post('transfer-orders/{id}/ship', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'ship']);
    Route::post('transfer-orders/{id}/receive', [\Modules\Inventory\Http\Controllers\TransferOrders::class, 'receive']);
    Route::get('item-groups', [\Modules\Inventory\Http\Controllers\ItemGroups::class, 'index']);
    Route::post('item-groups', [\Modules\Inventory\Http\Controllers\ItemGroups::class, 'store']);
    Route::get('item-groups/{id}', [\Modules\Inventory\Http\Controllers\ItemGroups::class, 'show']);
    Route::put('item-groups/{id}', [\Modules\Inventory\Http\Controllers\ItemGroups::class, 'update']);
    Route::delete('item-groups/{id}', [\Modules\Inventory\Http\Controllers\ItemGroups::class, 'destroy']);
    Route::get('reports/status', [\Modules\Inventory\Http\Controllers\Reports::class, 'status']);
    Route::get('reports/value', [\Modules\Inventory\Http\Controllers\Reports::class, 'value']);
    Route::get('reports/variants', [\Modules\Inventory\Http\Controllers\Reports::class, 'variants']);
    Route::get('reports/adjustments', [\Modules\Inventory\Http\Controllers\Reports::class, 'adjustments']);
    Route::get('reports/transfers', [\Modules\Inventory\Http\Controllers\Reports::class, 'transfers']);
    Route::get('reports/item-groups', [\Modules\Inventory\Http\Controllers\Reports::class, 'itemGroups']);
    Route::get('history', [\Modules\Inventory\Http\Controllers\History::class, 'index']);
});
