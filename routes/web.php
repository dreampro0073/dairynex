<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryRouteController;
use App\Http\Controllers\DeliveryBoyController;

Route::get('/', function () {
    return redirect(auth()->user()?->role()?->home() ?? '/login');
});

Route::middleware(['auth', 'auth.session', 'account:client_admin'])->group(function () {
Route::get('/deliveries', [\App\Http\Controllers\DeliveryController::class, 'index'])->name('deliveries.index');
Route::post('/deliveries/generate', [\App\Http\Controllers\DeliveryController::class, 'generate'])->name('deliveries.generate');
Route::post('/customers/{customer}/login', [\App\Http\Controllers\CustomerLoginController::class, 'store'])->name('customers.login');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/categories', [ProductCategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [ProductCategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [ProductCategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [ProductCategoryController::class, 'destroy'])->name('categories.destroy');
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

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/deliveries/{id}', [\App\Http\Controllers\DeliveryController::class, 'show'])->whereNumber('id')->name('deliveries.show');
    Route::post('/deliveries/{id}/complete', [\App\Http\Controllers\DeliveryController::class, 'complete'])->middleware(['account:delivery_boy', 'throttle:10,1'])->whereNumber('id')->name('deliveries.complete');
    Route::post('/deliveries/{id}/pin', [\App\Http\Controllers\DeliveryController::class, 'pin'])->middleware(['account:customer', 'throttle:3,1'])->whereNumber('id')->name('deliveries.pin');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'store'])->middleware('account:client_admin')->name('subscriptions.store');
    Route::post('/subscriptions/{id}/pause', [\App\Http\Controllers\SubscriptionController::class, 'pause'])->whereNumber('id')->name('subscriptions.pause');
    Route::get('/quantity-change-requests', [\App\Http\Controllers\QuantityChangeController::class, 'index'])->name('quantity-changes.index');
    Route::post('/subscriptions/{id}/quantity-change', [\App\Http\Controllers\QuantityChangeController::class, 'store'])->whereNumber('id')->name('quantity-changes.store');
    Route::put('/quantity-change-requests/{id}', [\App\Http\Controllers\QuantityChangeController::class, 'decide'])->middleware('account:client_admin')->whereNumber('id')->name('quantity-changes.decide');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/ledger', [\App\Http\Controllers\AccountingController::class, 'index'])->name('ledger.index');
    Route::get('/account/ledger', [\App\Http\Controllers\AccountingController::class, 'index'])->name('account.ledger');
    Route::get('/payments', [\App\Http\Controllers\AccountingController::class, 'index'])->name('payments.index');
    Route::post('/payments', [\App\Http\Controllers\AccountingController::class, 'store'])->name('payments.store');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'show'])->whereNumber('id')->name('invoices.show');
    Route::post('/invoices', [\App\Http\Controllers\InvoiceController::class, 'store'])->middleware('account:client_admin')->name('invoices.store');
    Route::post('/invoices/{id}/allocate', [\App\Http\Controllers\InvoiceController::class, 'allocate'])->middleware('account:client_admin')->whereNumber('id')->name('invoices.allocate');
});

Route::middleware(['auth', 'auth.session', 'account'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->whereNumber('id')->name('notifications.read');
});
