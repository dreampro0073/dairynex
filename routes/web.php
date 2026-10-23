<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryBoyController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryRouteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuantityChangeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WalkInSaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(auth()->user()?->role()?->home() ?? '/login');
});

Route::middleware(['auth', 'auth.session', 'account:client_admin'])->group(function () {
    Route::get('/quick-sale', [WalkInSaleController::class, 'quick'])->name('walk-in.quick');
    Route::post('/walk-in-sales', [WalkInSaleController::class, 'store'])->name('walk-in.store');
    Route::get('/walk-in-sales', [WalkInSaleController::class, 'index'])->name('walk-in.index');
    Route::get('/daily-sales-report', [WalkInSaleController::class, 'report'])->name('walk-in.report');
    Route::get('/walk-in-sales/{id}', [WalkInSaleController::class, 'show'])
        ->whereNumber('id')
        ->name('walk-in.show');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('/deliveries/generate', [DeliveryController::class, 'generate'])->name('deliveries.generate');
    Route::post('/customers/{customer}/login', [CustomerLoginController::class, 'store'])
        ->name('customers.login');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/categories', [ProductCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [ProductCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [ProductCategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{category}', [ProductCategoryController::class, 'destroy'])
        ->name('categories.destroy');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');

    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');

    Route::post('/products/{product}/rates', [ProductController::class, 'rate'])->name('products.rates');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');

    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');

    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');

    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::get('/routes', [DeliveryRouteController::class, 'index'])
        ->name('routes.index');

    Route::get('/routes/create', [DeliveryRouteController::class, 'create'])
        ->name('routes.create');

    Route::post('/routes', [DeliveryRouteController::class, 'store'])
        ->name('routes.store');

    Route::get('/routes/{deliveryRoute}/edit', [DeliveryRouteController::class, 'edit'])
        ->name('routes.edit');

    Route::put('/routes/{deliveryRoute}', [DeliveryRouteController::class, 'update'])
        ->name('routes.update');

    Route::delete('/routes/{deliveryRoute}', [DeliveryRouteController::class, 'destroy'])
        ->name('routes.destroy');

    Route::get(
        '/delivery-boys',
        [DeliveryBoyController::class, 'index']
    )->name('delivery-boys.index');

    Route::get(
        '/delivery-boys/create',
        [DeliveryBoyController::class, 'create']
    )->name('delivery-boys.create');

    Route::post(
        '/delivery-boys',
        [DeliveryBoyController::class, 'store']
    )->name('delivery-boys.store');

    Route::get(
        '/delivery-boys/{deliveryBoy}/edit',
        [DeliveryBoyController::class, 'edit']
    )->name('delivery-boys.edit');

    Route::put(
        '/delivery-boys/{deliveryBoy}',
        [DeliveryBoyController::class, 'update']
    )->name('delivery-boys.update');

    Route::delete(
        '/delivery-boys/{deliveryBoy}',
        [DeliveryBoyController::class, 'destroy']
    )->name('delivery-boys.destroy');

});

require __DIR__.'/auth.php';
require __DIR__.'/platform.php';

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show'])
        ->whereNumber('id')
        ->name('deliveries.show');

    Route::post('/deliveries/{id}/complete', [DeliveryController::class, 'complete'])
        ->middleware(['account:delivery_boy', 'throttle:10,1'])
        ->whereNumber('id')
        ->name('deliveries.complete');

    Route::post('/deliveries/{id}/pin', [DeliveryController::class, 'pin'])
        ->middleware(['account:customer', 'throttle:3,1'])
        ->whereNumber('id')
        ->name('deliveries.pin');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])
        ->middleware('account:client_admin')
        ->name('subscriptions.store');

    Route::post('/subscriptions/{id}/pause', [SubscriptionController::class, 'pause'])
        ->whereNumber('id')
        ->name('subscriptions.pause');

    Route::get('/quantity-change-requests', [QuantityChangeController::class, 'index'])
        ->name('quantity-changes.index');

    Route::post('/subscriptions/{id}/quantity-change', [QuantityChangeController::class, 'store'])
        ->whereNumber('id')
        ->name('quantity-changes.store');

    Route::put('/quantity-change-requests/{id}', [QuantityChangeController::class, 'decide'])
        ->middleware('account:client_admin')
        ->whereNumber('id')
        ->name('quantity-changes.decide');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/ledger', [AccountingController::class, 'index'])->name('ledger.index');
    Route::get('/account/ledger', [AccountingController::class, 'index'])->name('account.ledger');
    Route::get('/payments', [AccountingController::class, 'index'])->name('payments.index');
    Route::post('/payments', [AccountingController::class, 'store'])->name('payments.store');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])
        ->whereNumber('id')
        ->name('invoices.show');

    Route::post('/invoices', [InvoiceController::class, 'store'])
        ->middleware('account:client_admin')
        ->name('invoices.store');

    Route::post('/invoices/{id}/allocate', [InvoiceController::class, 'allocate'])
        ->middleware('account:client_admin')
        ->whereNumber('id')
        ->name('invoices.allocate');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{id}/read', [NotificationController::class, 'read'])
        ->whereNumber('id')
        ->name('notifications.read');
});
