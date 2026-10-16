<?php

use App\Http\Controllers\Platform\{ClientController, ConsoleController};
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->name('platform.')->middleware(['auth', 'auth.session', 'account:super_admin'])->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('dashboard');
    Route::resource('clients', ClientController::class)->except('destroy');
    Route::put('clients/{client}/status', [ClientController::class, 'status'])->name('clients.status');
    Route::post('clients/{client}/admins', [ClientController::class, 'admin'])->name('clients.admins.store');
    Route::put('clients/{client}/admins/{admin}', [ClientController::class, 'admin'])->whereNumber('admin')->name('clients.admins.update');
    Route::get('clients/{client}/login-details', [ClientController::class, 'loginDetails'])->name('clients.login-details');
    Route::post('clients/{client}/admins/{admin}/temporary-password', [ClientController::class, 'temporaryPassword'])->whereNumber('admin')->middleware('throttle:5,1')->name('clients.temporary-password');
    Route::post('clients/{client}/subscription', [ConsoleController::class, 'subscription'])->name('clients.subscription');
    Route::get('plans', [ConsoleController::class, 'plans'])->name('plans.index');
    Route::post('plans', [ConsoleController::class, 'savePlan'])->name('plans.store');
    Route::put('plans/{plan}', [ConsoleController::class, 'savePlan'])->name('plans.update');
    Route::delete('plans/{plan}', [ConsoleController::class, 'deletePlan'])->name('plans.destroy');
    Route::get('billing', [ConsoleController::class, 'billing'])->name('billing');
    Route::get('billing/export', [ConsoleController::class, 'exportBilling'])->name('billing.export');
    Route::post('billing/{invoice}/paid', [ConsoleController::class, 'paid'])->whereNumber('invoice')->name('billing.paid');
    Route::get('databases', [ConsoleController::class, 'databases'])->name('databases');
    Route::get('usage', [ConsoleController::class, 'usage'])->name('usage');
    Route::get('usage/export', [ConsoleController::class, 'exportUsage'])->name('usage.export');
    Route::get('support', [ConsoleController::class, 'support'])->name('support');
    Route::post('support', [ConsoleController::class, 'saveTicket'])->name('support.store');
    Route::put('support/{ticket}', [ConsoleController::class, 'saveTicket'])->whereNumber('ticket')->name('support.update');
    Route::get('settings', [ConsoleController::class, 'settings'])->name('settings');
    Route::put('settings', [ConsoleController::class, 'saveSettings'])->name('settings.update');
});
