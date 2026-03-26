<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/inventory')->group(function () {
    Route::get('warehouses', [\Modules\Inventory\Http\Controllers\Warehouses::class, 'index']);
    Route::get('stock', [\Modules\Inventory\Http\Controllers\Stock::class, 'index']);
    Route::get('stock/alerts', [\Modules\Inventory\Http\Controllers\Stock::class, 'alerts']);
    Route::get('reports/status', [\Modules\Inventory\Http\Controllers\Reports::class, 'status']);
    Route::get('reports/value', [\Modules\Inventory\Http\Controllers\Reports::class, 'value']);
    Route::get('history', [\Modules\Inventory\Http\Controllers\History::class, 'index']);
});
