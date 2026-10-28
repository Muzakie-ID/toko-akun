<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ponytail: frontend-first — semua halaman mock tanpa DB, ganti Controller + data asli saat backend
Route::get('/', fn () => Inertia::render('Catalog'))->name('home');
Route::get('/produk/{id}', fn (string $id) => Inertia::render('ProductDetail', ['id' => (int) $id]))->name('product.detail');
Route::get('/keranjang', fn () => Inertia::render('Cart'))->name('cart');
Route::get('/pesanan', fn () => Inertia::render('Orders'))->name('orders');
Route::get('/login', fn () => Inertia::render('Catalog'))->name('login'); // placeholder sampai auth dibuat
Route::get('/register', fn () => Inertia::render('Catalog'))->name('register'); // placeholder sampai auth dibuat
