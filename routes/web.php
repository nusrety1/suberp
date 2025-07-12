<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// todo: use prefix

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Products
Route::get('products', [ProductController::class, 'list'])
    ->middleware(['auth', 'verified'])->name('products');

Route::get('products/basic-list', [ProductController::class, 'listBasic'])
    ->middleware(['auth', 'verified'])->name('products-basic-list');

Route::get('add-product', function () {
    return Inertia::render('AddProduct');
})->middleware(['auth', 'verified'])->name('add-product');

// Customers
Route::get('customers', [CustomerController::class, 'list'])
    ->middleware(['auth', 'verified'])->name('customers');

Route::get('customers/basic-list', [CustomerController::class, 'listBasic'])
    ->middleware(['auth', 'verified'])->name('customers-basic-list');

Route::get('add-customer', function () {
    return Inertia::render('AddCustomer');
})->middleware(['auth', 'verified'])->name('add-customer');

// Purchases
Route::get('purchases', [PurchaseController::class, 'list'])
    ->middleware(['auth', 'verified'])->name('purchases');

Route::get('purchases/{id}/details', [PurchaseController::class, 'details'])
    ->middleware(['auth', 'verified'])->name('purchase-details');

Route::get('add-purchase', function () {
    return Inertia::render('AddPurchase');
})->middleware(['auth', 'verified'])->name('add-purchase');

// Post Apis
Route::post('customer', [CustomerController::class, 'create'])
    ->name('create-customer');

Route::post('product', [ProductController::class, 'create'])
    ->name('create-product');

Route::post('purchase/create', [PurchaseController::class, 'create'])
    ->name('create-purchase');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
