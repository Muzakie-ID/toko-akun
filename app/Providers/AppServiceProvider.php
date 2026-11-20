<?php

namespace App\Providers;

use App\Services\Settings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance per request agar pengaturan cukup dibaca sekali dari DB
        $this->app->scoped(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
