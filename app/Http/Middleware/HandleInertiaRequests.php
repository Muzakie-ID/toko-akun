<?php

namespace App\Http\Middleware;

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
            'auth' => fn () => $request->user()
                ? ['user' => ['id' => $request->user()->id, 'name' => $request->user()->name, 'role' => $request->user()->role]]
                : null,
            'cart_count' => fn () => 0,
            // ponytail: dibutuhkan form logout native di Navbar; Inertia form otomatis via cookie XSRF
            'csrf_token' => fn () => csrf_token(),
        ];
    }
}
