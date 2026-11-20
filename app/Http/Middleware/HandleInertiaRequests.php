<?php

namespace App\Http\Middleware;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'adminBadges' => fn () => $request->user()?->isStaff() ? [
                'pesanan' => Order::where('status', OrderStatus::MenungguVerifikasi)->count(),
                'menungguStok' => Order::where('status', OrderStatus::Diproses)->count(),
            ] : null,
            'auth' => fn () => $request->user()
                ? ['user' => ['id' => $request->user()->id, 'name' => $request->user()->name, 'role' => $request->user()->role]]
                : null,
            'cart_count' => fn () => 0,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // ponytail: dibutuhkan form logout native di Navbar; Inertia form otomatis via cookie XSRF
            'csrf_token' => fn () => csrf_token(),
        ];
    }
}
