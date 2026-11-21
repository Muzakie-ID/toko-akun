<?php

namespace Tests\Feature;

use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TermsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_terms_with_values_from_settings(): void
    {
        $this->withoutVite();
        app(Settings::class)->set([
            'manual.batas_jam' => 12,
            'topup.min' => 25000,
            'reseller.min_saldo' => 2000000,
            'store.whatsapp' => '628123',
        ]);

        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Terms')
                ->where('pembayaran.manual_jam', 12)
                ->where('topup.min', 25000)
                ->where('reseller.batas', 2000000)
                ->where('reseller.otomatis', true)
                ->where('toko.whatsapp', '628123'));
    }
}
