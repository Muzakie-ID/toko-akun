<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('nama')
                ->get(['id', 'nama', 'slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Kategori disimpan.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori masih dipakai produk. Pindahkan produknya dulu.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    /**
     * @return array{nama: string, slug: string}
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $request->merge(['slug' => Str::slug((string) $request->input('nama'))]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'slug' => ['required', Rule::unique('categories', 'slug')->ignore($category)],
        ], [
            'slug.unique' => 'Kategori dengan nama ini sudah ada.',
        ]);
    }
}
