<?php

use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook', [\Modules\Stripe\Http\Controllers\Webhook::class, 'handle'])->name('stripe.webhook.handle');
