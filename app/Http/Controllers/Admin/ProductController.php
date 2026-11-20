<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisAkun;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with('category:id,nama')
            ->withCount(['variants', 'stocks'])
            ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'nama' => $product->nama,
                'kategori' => $product->category->nama,
                'jumlah_varian' => $product->variants_count,
                'jumlah_stok' => $product->stocks_count,
                'is_aktif' => $product->is_aktif,
                'is_populer' => $product->is_populer,
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => $this->categoryOptions(),
            'variants' => [],
            'jenisAkunOptions' => $this->jenisAkunOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return to_route('admin.products.edit', $product)
            ->with('success', 'Produk dibuat. Sekarang tambahkan varian (tipe akun & durasi).');
    }

    public function edit(Product $product): Response
    {
        $variants = $product->variants()
            ->withCount('stocks')
            ->orderBy('jenis_akun')
            ->orderBy('durasi_hari')
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'jenis_akun' => $variant->jenis_akun->value,
                'jenis_akun_label' => $variant->jenis_akun->label(),
                'durasi_hari' => $variant->durasi_hari,
                'durasi_label' => $variant->durasi_label,
                'harga' => $variant->harga,
                'harga_asli' => $variant->harga_asli,
                'harga_reseller' => $variant->harga_reseller,
                'is_aktif' => $variant->is_aktif,
                'jumlah_stok' => $variant->stocks_count,
            ]);

        return Inertia::render('Admin/Products/Form', [
            'product' => $product->only(['id', 'category_id', 'nama', 'slug', 'deskripsi', 'garansi_hari', 'is_aktif', 'is_populer']),
            'categories' => $this->categoryOptions(),
            'variants' => $variants,
            'jenisAkunOptions' => $this->jenisAkunOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return back()->with('success', 'Produk disimpan.');
    }

    /**
     * Produk yang sudah punya stok tidak bisa dihapus (riwayat kredensial & order harus tetap ada);
     * nonaktifkan saja agar tidak tampil di katalog.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stocks()->exists()) {
            return back()->with('error', 'Produk ini sudah punya stok, jadi tidak bisa dihapus. Nonaktifkan saja agar tidak tampil di katalog.');
        }

        $product->delete();

        return to_route('admin.products.index')->with('success', "Produk \"{$product->nama}\" dihapus.");
    }

    /**
     * @return list<array{id: int, nama: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()->orderBy('nama')->get(['id', 'nama'])->toArray();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function jenisAkunOptions(): array
    {
        return array_map(
            fn (JenisAkun $jenis): array => ['value' => $jenis->value, 'label' => $jenis->label()],
            JenisAkun::cases(),
        );
    }
}
