<?php

namespace App\Http\Controllers;

use App\Services\Settings;
use App\Services\TermsContent;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    /**
     * Syarat & ketentuan yang diedit admin; placeholder diisi nilai dari Pengaturan.
     */
    public function __invoke(TermsContent $terms, Settings $settings): Response
    {
        return Inertia::render('Terms', [
            ...$terms->rendered(),
            'whatsapp' => $settings->get('store.whatsapp') ?: null,
        ]);
    }
}
