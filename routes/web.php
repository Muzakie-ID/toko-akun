<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ponytail: frontend-first — semua halaman mock tanpa DB, ganti Controller + data asli saat backend
Route::get('/', fn () => Inertia::render('Catalog'))->name('home');
Route::get('/produk/{id}', fn (string $id) => Inertia::render('ProductDetail', ['id' => (int) $id]))->name('product.detail');
Route::get('/keranjang', fn () => Inertia::render('Cart'))->name('cart');
Route::get('/pesanan', fn () => Inertia::render('Orders'))->name('orders');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/auth/google', [AuthController::class, 'googleRedirect'])->name('auth.google');
});

Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('auth.google.callback');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
