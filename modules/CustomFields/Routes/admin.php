<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'custom-fields' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('custom-fields', function () {
    Route::resource('fields', 'Fields');
});
