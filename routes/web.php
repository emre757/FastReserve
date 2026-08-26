<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Offerings\OfferingController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::resource('offerings', OfferingController::class)
        ->only(['index', 'show'])
        ->where(['offering' => '[0-9]+']); // to work with index, create etc in case id cannot be specified (like offerings or offerings/create)
});

// TODO: make middleware to check if user's current team owns the offering
Route::middleware(['auth', EnsureTeamMembership::class])->group(function () {
    Route::resource('offerings', OfferingController::class)
        ->except('show', 'index')
        ->where(['offering' => '[0-9]+']);
});

require __DIR__.'/settings.php';
