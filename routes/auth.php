<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordController::class, 'form'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/delivery', [PortalController::class, 'delivery'])
        ->middleware('account:delivery_boy')
        ->name('delivery.dashboard');

    Route::get('/customer', [PortalController::class, 'customer'])
        ->middleware('account:customer')
        ->name('customer.dashboard');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.change.update');
});
