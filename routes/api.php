<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Litepie\Teams\Http\Controllers\TeamsController;
use Litepie\Teams\Http\Controllers\TeamMembersController;
use Litepie\Teams\Http\Controllers\TeamInvitationsController;

/*
|--------------------------------------------------------------------------
| Teams API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['api', 'auth', 'tenant'])->prefix('api/teams')->name('teams.')->group(function () {
    
    // Team Management Routes
    Route::apiResource('teams', TeamsController::class);
    
    // Team Action Routes
    Route::prefix('teams/{team}')->group(function () {
        Route::post('activate', [TeamsController::class, 'activate'])->name('activate');
        Route::post('suspend', [TeamsController::class, 'suspend'])->name('suspend');
        Route::post('restore', [TeamsController::class, 'restore'])->name('restore');
        Route::get('stats', [TeamsController::class, 'stats'])->name('stats');
        
        // Team Members Routes
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', [TeamMembersController::class, 'index'])->name('index');
            Route::post('/', [TeamMembersController::class, 'store'])->name('store');
            Route::get('{member}', [TeamMembersController::class, 'show'])->name('show');
            Route::put('{member}', [TeamMembersController::class, 'update'])->name('update');
            Route::delete('{member}', [TeamMembersController::class, 'destroy'])->name('destroy');
            Route::post('{member}/promote', [TeamMembersController::class, 'promote'])->name('promote');
            Route::post('{member}/demote', [TeamMembersController::class, 'demote'])->name('demote');
        });
        
        // Team Invitations Routes
        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/', [TeamInvitationsController::class, 'index'])->name('index');
            Route::post('/', [TeamInvitationsController::class, 'store'])->name('store');
            Route::get('{invitation}', [TeamInvitationsController::class, 'show'])->name('show');
            Route::post('{invitation}/resend', [TeamInvitationsController::class, 'resend'])->name('resend');
            Route::delete('{invitation}', [TeamInvitationsController::class, 'destroy'])->name('destroy');
        });
    });
});

// Public invitation routes (no auth required)
Route::middleware(['api', 'tenant'])->prefix('api/invitations')->name('invitations.')->group(function () {
    Route::get('{token}', [TeamInvitationsController::class, 'showByToken'])->name('show_by_token');
    Route::post('{token}/accept', [TeamInvitationsController::class, 'accept'])->name('accept');
    Route::post('{token}/decline', [TeamInvitationsController::class, 'decline'])->name('decline');
});
