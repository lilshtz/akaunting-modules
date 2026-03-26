<?php

use Illuminate\Support\Facades\Route;
use Modules\Receipts\Http\Controllers\Api\Receipts;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {
    Route::get('receipts', [Receipts::class, 'index'])->name('api.receipts.index');
    Route::get('receipts/pending', [Receipts::class, 'pending'])->name('api.receipts.pending');
    Route::get('receipts/{id}', [Receipts::class, 'show'])->name('api.receipts.show');
    Route::post('receipts/upload', [Receipts::class, 'upload'])->name('api.receipts.upload');
    Route::post('receipts/{id}/process', [Receipts::class, 'process'])->name('api.receipts.process');
});
