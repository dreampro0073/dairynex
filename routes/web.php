<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryRouteController;
use App\Http\Controllers\DeliveryBoyController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

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

Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');



Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');

Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');

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

