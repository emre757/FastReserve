<?php

use App\Http\Controllers\Payments\Stripe\StripeAccountWebhookController;
use App\Http\Controllers\Payments\Stripe\StripePaymentWebhookController;

// stripe webhooks

Route::post(
    'webhooks/stripe/accounts',
    StripeAccountWebhookController::class,
)->name('webhooks.stripe.accounts');

Route::post(
    'webhooks/stripe/payments',
    StripePaymentWebhookController::class,
)->name('webhooks.stripe.payments');
