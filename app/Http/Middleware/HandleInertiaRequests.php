<?php

namespace App\Http\Middleware;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Saldo\ResellerStatus;
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
                ? ['user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'role' => $request->user()->role,
                    'saldo' => $request->user()->saldo,
                    'harga_reseller' => app(ResellerStatus::class)->appliesTo($request->user()),
                ]]
                : null,
            'cart_count' => fn () => (int) $request->user()?->cartItems()->sum('qty'),
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
