<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TermsRequest;
use App\Services\TermsContent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Khusus admin: mengedit isi halaman Syarat & Ketentuan.
 */
class TermsController extends Controller
{
    public function edit(TermsContent $terms): Response
    {
        return Inertia::render('Admin/Terms', [
            'konten' => $terms->raw(),
            'diperbarui' => $terms->rendered()['diperbarui'],
            'bawaan' => TermsContent::defaults(),
            'ikon' => TermsContent::ICONS,
            'placeholder' => collect(TermsContent::PLACEHOLDERS)
                ->map(fn (string $label, string $key): array => ['kode' => "{{$key}}", 'label' => $label, 'nilai' => $terms->placeholderValues()[$key]])
                ->values(),
        ]);
    }

    public function update(TermsRequest $request, TermsContent $terms): RedirectResponse
    {
        $terms->save($request->validated(), $request->boolean('perbarui_tanggal'));

        return back()->with('success', 'Syarat & ketentuan disimpan.');
    }
}
