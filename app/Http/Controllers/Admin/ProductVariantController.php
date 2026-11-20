<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function store(ProductVariantRequest $request, Product $product): RedirectResponse
    {
        $product->variants()->create($request->validated());

        return back()->with('success', 'Varian ditambahkan.');
    }

    public function update(ProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->validated());

        return back()->with('success', 'Varian disimpan.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        if ($variant->stocks()->exists()) {
            return back()->with('error', 'Varian ini sudah punya stok, jadi tidak bisa dihapus. Nonaktifkan saja.');
        }

        $variant->delete();

        return back()->with('success', 'Varian dihapus.');
    }
}
