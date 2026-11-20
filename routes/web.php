<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\StockImportController;
use App\Http\Controllers\Admin\TopupController as AdminTopupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QrismuzWebhookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/produk/{product:slug}', [CatalogController::class, 'show'])->name('product.detail');

Route::middleware('auth')->group(function () {
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart');
    Route::post('/keranjang', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/keranjang/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/keranjang/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/checkout', [CartController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');

    Route::get('/akun', [AccountController::class, 'edit'])->name('account');
    Route::put('/akun', [AccountController::class, 'updateProfile'])->name('account.update');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password');

    Route::get('/saldo', [SaldoController::class, 'index'])->name('saldo');
    Route::post('/saldo/topup', [SaldoController::class, 'storeTopup'])->middleware('throttle:10,1')->name('saldo.topup');
    Route::get('/saldo/topup/{topup}', [SaldoController::class, 'showTopup'])->name('saldo.topup.show');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/ulasan/{item}', [ReviewController::class, 'store'])->middleware('throttle:20,1')->name('reviews.store');
    Route::post('/pesanan/{order}/bukti', [OrderController::class, 'uploadProof'])->middleware('throttle:10,1')->name('orders.proof');
});

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
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('/pesanan/{order}/bukti', [AdminOrderController::class, 'proof'])->name('orders.proof');
    Route::post('/pesanan/{order}/verifikasi', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/pesanan/{order}/tolak', [AdminOrderController::class, 'reject'])->name('orders.reject');
    Route::post('/pesanan/{order}/kirim-ulang', [AdminOrderController::class, 'retry'])->name('orders.retry');
    Route::post('/pesanan/{order}/batal', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/pesanan/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
    Route::put('/pesanan/{order}/catatan', [AdminOrderController::class, 'note'])->name('orders.note');

    Route::resource('produk', ProductController::class)
        ->except('show')
        ->parameters(['produk' => 'product'])
        ->names('products');
    Route::post('/produk/{product}/varian', [ProductVariantController::class, 'store'])->name('variants.store');
    Route::put('/varian/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('/varian/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

    Route::get('/topup', [AdminTopupController::class, 'index'])->name('topups.index');

    Route::get('/ulasan', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/ulasan/{review}/tampil', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('/ulasan/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/stok', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stok/import', [StockImportController::class, 'create'])->name('stocks.import');
    Route::post('/stok/import', [StockImportController::class, 'store'])->name('stocks.import.store');
    Route::put('/stok/{stock}', [StockController::class, 'update'])->name('stocks.update');
    Route::delete('/stok/{stock}', [StockController::class, 'destroy'])->name('stocks.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::post('/pengguna/{user}/saldo', [UserController::class, 'adjustSaldo'])->name('users.saldo');

        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/pengaturan/tes-qris', [SettingController::class, 'testQris'])->name('settings.test-qris');
    });
});
