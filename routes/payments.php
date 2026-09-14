<?php

use App\Http\Controllers\Payments\CompanyPaymentAccountController;
use App\Http\Controllers\Payments\OnboardingController;
use App\Http\Controllers\Payments\PaymentProcessingController;
use App\Http\Controllers\Payments\ReservationPaymentController;

// currently only stripe is supported, so application is directly injecting it rather than choosing which provider
// this can be changed easily in the future
Route::middleware(['auth', 'can:managePayments,team'])->group(function () {
    // onboarding
    Route::post(
        'companies/{team}/payment-account',
        [OnboardingController::class, 'store'],
    )->name('companies.payment-account.store');

    Route::get(
        'companies/{team}/payment-account/return',
        [OnboardingController::class, 'returned'],
    )->name('companies.payment-account.return');

    Route::get(
        'companies/{team}/payment-account/refresh',
        [OnboardingController::class, 'refresh'],
    )->name('companies.payment-account.refresh');

    // account management
    Route::delete(
        'companies/{team}/payment-account',
        [CompanyPaymentAccountController::class, 'delete'],
    )->name('companies.payment-account.destroy');
});

// TODO: protection against paying after reservation got expired or spots filled or booking deadline was met
Route::middleware(['auth'])->group(function () {
    Route::post('payment/reservations/{reservation}/checkout', [ReservationPaymentController::class, 'store'])
        ->name('reservations.payment.checkout');

    Route::get('payment/reservations/{reservation}/processing', PaymentProcessingController::class)
        ->name('reservations.payment.processing');
});
