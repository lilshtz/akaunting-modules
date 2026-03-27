<?php

use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook', [\Modules\Stripe\Http\Controllers\Webhook::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('stripe.webhook.handle');
