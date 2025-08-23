<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Litepie\Teams\Http\Controllers\TeamsController;

/*
|--------------------------------------------------------------------------
| Teams Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for teams functionality.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware(['web', 'auth', 'tenant'])->prefix('teams')->name('teams.')->group(function () {
    
    // Team Dashboard and Management
    Route::get('/', [TeamsController::class, 'index'])->name('index');
    Route::get('/create', [TeamsController::class, 'create'])->name('create');
    Route::post('/', [TeamsController::class, 'store'])->name('store');
    Route::get('/{team}', [TeamsController::class, 'show'])->name('show');
    Route::get('/{team}/edit', [TeamsController::class, 'edit'])->name('edit');
    Route::put('/{team}', [TeamsController::class, 'update'])->name('update');
    Route::delete('/{team}', [TeamsController::class, 'destroy'])->name('destroy');
    
    // Team Settings and Actions
    Route::prefix('{team}')->group(function () {
        Route::get('settings', [TeamsController::class, 'settings'])->name('settings');
        Route::put('settings', [TeamsController::class, 'updateSettings'])->name('settings.update');
        
        Route::post('activate', [TeamsController::class, 'activate'])->name('activate');
        Route::post('suspend', [TeamsController::class, 'suspend'])->name('suspend');
        Route::post('restore', [TeamsController::class, 'restore'])->name('restore');
    });
});

// Public invitation routes
Route::middleware(['web', 'tenant'])->prefix('invitations')->name('invitations.')->group(function () {
    Route::get('{token}', function ($token) {
        return view('teams::invitations.show', compact('token'));
    })->name('show');
    
    Route::post('{token}/accept', function ($token) {
        // Handle invitation acceptance
        return redirect()->route('teams.index')->with('success', 'Invitation accepted!');
    })->name('accept');
    
    Route::post('{token}/decline', function ($token) {
        // Handle invitation decline
        return redirect()->back()->with('info', 'Invitation declined.');
    })->name('decline');
});
