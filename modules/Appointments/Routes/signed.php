<?php

use Illuminate\Support\Facades\Route;

Route::signed('appointments', function () {
    Route::get('book/{token}', 'Modules\Appointments\Http\Controllers\PublicBooking@show')->name('booking.show');
    Route::post('book/{token}', 'Modules\Appointments\Http\Controllers\PublicBooking@store')->name('booking.store');
});
