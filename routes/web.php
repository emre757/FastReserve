<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Offerings\OfferingController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');

    Route::resource('companies.offerings', OfferingController::class)
        ->only(['index', 'show'])
        ->parameters(['companies' => 'team'])
        ->shallow()
        ->where(['offering' => '[0-9]+'])
        ->scoped(); // to work with index, create etc in case id cannot be specified (like offerings or offerings/create)
});

Route::middleware(['auth', EnsureTeamMembership::class])->group(function () {
    Route::resource('offerings', OfferingController::class)
        ->except('show', 'index')
        ->where(['offering' => '[0-9]+']);
});

require __DIR__.'/settings.php';
