<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\StockImportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QrismuzWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ponytail: frontend-first — semua halaman mock tanpa DB, ganti Controller + data asli saat backend
Route::get('/', fn () => Inertia::render('Catalog'))->name('home');
Route::get('/produk/{id}', fn (string $id) => Inertia::render('ProductDetail', ['id' => (int) $id]))
    ->whereNumber('id')
    ->name('product.detail');
Route::get('/keranjang', fn () => Inertia::render('Cart'))->name('cart');
Route::get('/pesanan', fn () => Inertia::render('Orders'))->middleware('auth')->name('orders');
Route::get('/pesanan/{order}', [OrderController::class, 'show'])->middleware('auth')->name('orders.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');

    Route::get('/auth/google', [AuthController::class, 'googleRedirect'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('auth.google.callback');

    Route::get('/lupa-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    // Nama 'password.reset' dipakai notifikasi reset bawaan Laravel untuk membuat URL di email
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/webhooks/qrismuz', QrismuzWebhookController::class)->middleware('throttle:120,1')->name('webhooks.qrismuz');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin,karyawan'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('produk', ProductController::class)
        ->except('show')
        ->parameters(['produk' => 'product'])
        ->names('products');
    Route::post('/produk/{product}/varian', [ProductVariantController::class, 'store'])->name('variants.store');
    Route::put('/varian/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('/varian/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

    Route::get('/stok/import', [StockImportController::class, 'create'])->name('stocks.import');
    Route::post('/stok/import', [StockImportController::class, 'store'])->name('stocks.import.store');
});
