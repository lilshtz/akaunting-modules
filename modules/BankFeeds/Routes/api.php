<?php

use Illuminate\Support\Facades\Route;
use Modules\BankFeeds\Http\Controllers\Api\BankFeeds;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {
    Route::get('bank-feeds/imports', [BankFeeds::class, 'imports'])->name('api.bank-feeds.imports');
    Route::get('bank-feeds/transactions', [BankFeeds::class, 'transactions'])->name('api.bank-feeds.transactions');
    Route::get('bank-feeds/transactions/{id}', [BankFeeds::class, 'show'])->name('api.bank-feeds.transactions.show');
});
