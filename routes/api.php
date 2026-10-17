<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'account'])->group(function () {
        Route::get('me', MeController::class);
        Route::delete('auth/token', [AuthTokenController::class, 'destroy']);
        Route::get('deliveries', [DeliveryController::class, 'index']);
        Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])->whereNumber('delivery');
        Route::post('deliveries/{delivery}/complete', [DeliveryController::class, 'complete'])->middleware(['account:delivery_boy', 'throttle:10,1'])->whereNumber('delivery');
        Route::post('deliveries/{delivery}/pin', [DeliveryController::class, 'issuePin'])->middleware(['account:customer', 'throttle:3,1'])->whereNumber('delivery');
        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->middleware('account:client_admin');
        Route::post('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause'])->whereNumber('subscription');
    });
});
