<?php

namespace App\Http\Controllers;

use App\Enums\JenisAkun;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'array'],
            'kategori.*' => ['string'],
            'jenis' => ['nullable', 'array'],
            'jenis.*' => [Rule::enum(JenisAkun::class)],
            'durasi' => ['nullable', 'array'],
            'durasi.*' => ['integer'],
            'harga_max' => ['nullable', 'integer', 'min:0'],
            'urut' => ['nullable', Rule::in(['populer', 'terbaru', 'termurah', 'termahal'])],
        ]);

        $products = $this->activeProducts();
        $user = $request->user();

        $cards = $products
            ->map(fn (Product $product) => $this->card($product, $user, $filters))
            ->filter()
            ->when($filters['q'] ?? null, fn ($c, $q) => $c->filter(fn (array $card) => str_contains(mb_strtolower($card['nama']), mb_strtolower($q))))
            ->when($filters['kategori'] ?? null, fn ($c, $slugs) => $c->whereIn('kategori_slug', $slugs))
            ->when($filters['harga_max'] ?? null, fn ($c, $max) => $c->where('harga', '<=', $max));

        $cards = match ($filters['urut'] ?? 'populer') {
            'terbaru' => $cards->sortByDesc('id'),
            'termurah' => $cards->sortBy('harga'),
            'termahal' => $cards->sortByDesc('harga'),
            default => $cards->sortByDesc(fn (array $card) => [$card['is_populer'], $card['terjual']]),
        };

        $allVariants = $products->flatMap->variants;

        return Inertia::render('Catalog', [
            'produk' => $cards->values(),
            'terlaris' => $products->sortByDesc('total_terjual')->take(6)
                ->map(fn (Product $product) => $this->card($product, $user, []))->filter()->values(),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'kategori' => $filters['kategori'] ?? [],
                'jenis' => $filters['jenis'] ?? [],
                'durasi' => array_map('intval', $filters['durasi'] ?? []),
                'harga_max' => $filters['harga_max'] ?? null,
                'urut' => $filters['urut'] ?? 'populer',
            ],
            'opsi' => [
                'kategori' => Category::query()->orderBy('nama')->get()->map(fn (Category $category): array => [
                    'value' => $category->slug,
                    'label' => $category->nama,
                    'jumlah' => $products->where('category_id', $category->id)->count(),
                ])->where('jumlah', '>', 0)->values(),
                'jenis' => collect(JenisAkun::cases())->map(fn (JenisAkun $jenis): array => [
                    'value' => $jenis->value,
                    'label' => $jenis->label(),
                    'jumlah' => $allVariants->where('jenis_akun', $jenis)->pluck('product_id')->unique()->count(),
                ]),
                'durasi' => $allVariants->groupBy('durasi_hari')->sortKeys()->map(fn ($variants, $hari): array => [
                    'value' => (int) $hari,
                    'label' => ProductVariant::labelDurasi((int) $hari),
                    'jumlah' => $variants->pluck('product_id')->unique()->count(),
                ])->values(),
                'harga_tertinggi' => (int) $allVariants->max('harga'),
            ],
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_aktif, 404);

        $product->load(['category', 'variants' => fn ($query) => $query->where('is_aktif', true)->withCount('availableSlots')->orderBy('durasi_hari')]);
        $user = $request->user();

        return Inertia::render('ProductDetail', [
            'produk' => [
                'id' => $product->id,
                'nama' => $product->nama,
                'slug' => $product->slug,
                'deskripsi' => $product->deskripsi,
                'kategori' => $product->category->nama,
                'kategori_slug' => $product->category->slug,
                'garansi_hari' => $product->garansi_hari,
                'terjual' => $product->total_terjual,
                'rating' => (float) $product->rating,
            ],
            'varian' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'jenis_akun' => $variant->jenis_akun->value,
                'jenis_akun_label' => $variant->jenis_akun->label(),
                'durasi_hari' => $variant->durasi_hari,
                'durasi_label' => $variant->durasi_label,
                'harga' => $variant->priceFor($user),
                'harga_asli' => $variant->harga_asli,
                'stok' => $variant->available_slots_count,
            ]),
            'hargaReseller' => $user?->role?->value === 'reseller',
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    private function activeProducts(): Collection
    {
        return Product::query()
            ->with(['category', 'variants' => fn ($query) => $query->where('is_aktif', true)->withCount('availableSlots')])
            ->where('is_aktif', true)
            ->whereHas('variants', fn ($query) => $query->where('is_aktif', true))
            ->get();
    }

    /**
     * Kartu katalog menampilkan varian termurah yang lolos filter tipe & durasi.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    private function card(Product $product, ?User $user, array $filters): ?array
    {
        $variants = $product->variants
            ->when($filters['jenis'] ?? null, fn ($v, $jenis) => $v->filter(fn (ProductVariant $variant) => in_array($variant->jenis_akun->value, $jenis, true)))
            ->when($filters['durasi'] ?? null, fn ($v, $durasi) => $v->whereIn('durasi_hari', array_map('intval', $durasi)));

        $cheapest = $variants->sortBy(fn (ProductVariant $variant) => $variant->priceFor($user))->first();

        if (! $cheapest) {
            return null;
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'nama' => $product->nama,
            'kategori_slug' => $product->category->slug,
            'harga' => $cheapest->priceFor($user),
            'harga_asli' => $cheapest->harga_asli,
            'jenis_akun_label' => $cheapest->jenis_akun->label(),
            'durasi_label' => $cheapest->durasi_label,
            'jumlah_varian' => $product->variants->count(),
            'rating' => (float) $product->rating,
            'terjual' => $product->total_terjual,
            'is_populer' => $product->is_populer,
            'stok_ada' => $product->variants->sum('available_slots_count') > 0,
        ];
    }
}
