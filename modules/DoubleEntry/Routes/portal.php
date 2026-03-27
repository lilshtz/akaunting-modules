<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/double-entry' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('double-entry', function () {
    //
});
