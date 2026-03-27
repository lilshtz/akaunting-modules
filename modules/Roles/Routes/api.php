<?php

use Illuminate\Support\Facades\Route;
use Modules\Roles\Http\Controllers\Api\Permissions;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/roles')->group(function () {
    Route::get('permissions/current', [Permissions::class, 'current'])->name('api.roles.permissions.current');
    Route::get('permissions/check/{ability}', [Permissions::class, 'check'])->name('api.roles.permissions.check');
});
