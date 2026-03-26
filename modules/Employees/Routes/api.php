<?php

use Illuminate\Support\Facades\Route;
use Modules\Employees\Http\Controllers\Api\Departments as ApiDepartments;
use Modules\Employees\Http\Controllers\Api\Employees as ApiEmployees;

Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {
    Route::apiResource('employees', ApiEmployees::class)->except(['destroy']);
    Route::apiResource('departments', ApiDepartments::class)->only(['index', 'show']);
});
