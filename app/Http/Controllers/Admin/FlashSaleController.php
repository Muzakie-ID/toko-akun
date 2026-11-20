<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlashSaleRequest;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    private const STATUS_LABEL = [
        'berlangsung' => ['Berlangsung', 'emerald'],
        'akan_datang' => ['Akan datang', 'blue'],
        'selesai' => ['Selesai', 'slate'],
        'nonaktif' => ['Nonaktif', 'slate'],
    ];

    public function index(): Response
    {
        $sales = FlashSale::query()
            ->withCount('items')
            ->with(['items' => fn ($query) => $query->withCount('terpakaiItems')])
            ->orderByDesc('mulai_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(function (FlashSale $sale): array {
                $status = $sale->status();

                return [
                    'id' => $sale->id,
                    'nama' => $sale->nama,
                    'mulai' => $sale->mulai_at->translatedFormat('d M Y, H:i'),
                    'selesai' => $sale->selesai_at->translatedFormat('d M Y, H:i'),
                    'selesai_at' => $sale->selesai_at->toIso8601String(),
                    'status' => $status,
                    'status_label' => self::STATUS_LABEL[$status][0],
                    'status_tone' => self::STATUS_LABEL[$status][1],
                    'jumlah_varian' => $sale->items_count,
                    'terjual' => (int) $sale->items->sum('terpakai_items_count'),
                ];
            });

        return Inertia::render('Admin/FlashSales/Index', ['sales' => $sales]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/FlashSales/Form', [
            'sale' => null,
            'variants' => $this->variantOptions(),
        ]);
    }

    public function store(FlashSaleRequest $request): RedirectResponse
    {
        $sale = DB::transaction(function () use ($request): FlashSale {
            $sale = FlashSale::create($this->saleAttributes($request));
            $this->syncItems($sale, $request->input('items'));

            return $sale;
        });

        return to_route('admin.flash-sales.edit', $sale)->with('success', 'Flash sale dibuat.');
    }

    public function edit(FlashSale $flashSale): Response
    {
        $flashSale->load(['items' => fn ($query) => $query->withCount('terpakaiItems')]);

        return Inertia::render('Admin/FlashSales/Form', [
            'sale' => [
                'id' => $flashSale->id,
                'nama' => $flashSale->nama,
                'mulai_at' => $flashSale->mulai_at->format('Y-m-d\TH:i'),
                'selesai_at' => $flashSale->selesai_at->format('Y-m-d\TH:i'),
                'is_aktif' => $flashSale->is_aktif,
                'status' => $flashSale->status(),
                'items' => $flashSale->items->map(fn (FlashSaleItem $item): array => [
                    'product_variant_id' => $item->product_variant_id,
                    'harga_sale' => $item->harga_sale,
                    'kuota' => $item->kuota,
                    'terjual' => $item->terpakai_items_count,
                ]),
            ],
            'variants' => $this->variantOptions(),
        ]);
    }

    public function update(FlashSaleRequest $request, FlashSale $flashSale): RedirectResponse
    {
        DB::transaction(function () use ($request, $flashSale): void {
            $flashSale->update($this->saleAttributes($request));
            $this->syncItems($flashSale, $request->input('items'));
        });

        return back()->with('success', 'Flash sale disimpan.');
    }

    /**
     * Sale yang sudah ada pembelinya tidak dihapus (riwayat harga pesanan); cukup dinonaktifkan.
     */
    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $hasOrders = FlashSaleItem::where('flash_sale_id', $flashSale->id)->whereHas('orderItems')->exists();

        if ($hasOrders) {
            return back()->with('error', 'Flash sale ini sudah ada pembelinya, jadi tidak bisa dihapus. Nonaktifkan saja.');
        }

        $flashSale->delete();

        return to_route('admin.flash-sales.index')->with('success', "Flash sale \"{$flashSale->nama}\" dihapus.");
    }

    /**
     * @return array{nama: string, mulai_at: Carbon, selesai_at: Carbon, is_aktif: bool}
     */
    private function saleAttributes(FlashSaleRequest $request): array
    {
        return [
            'nama' => $request->input('nama'),
            'mulai_at' => $request->date('mulai_at'),
            'selesai_at' => $request->date('selesai_at'),
            'is_aktif' => $request->boolean('is_aktif'),
        ];
    }

    /**
     * Sinkron per varian: yang ada diperbarui (kuota terpakai tetap terhitung), yang baru dibuat,
     * yang dihapus dari form dibuang (pesanan lama tetap menyimpan harganya sendiri).
     *
     * @param  list<array{product_variant_id: int, harga_sale: int, kuota: int|null}>  $items
     */
    private function syncItems(FlashSale $sale, array $items): void
    {
        $variantIds = collect($items)->pluck('product_variant_id')->map(fn ($id) => (int) $id);
        $sale->items()->whereNotIn('product_variant_id', $variantIds)->get()->each->delete();

        foreach ($items as $item) {
            $sale->items()->updateOrCreate(
                ['product_variant_id' => (int) $item['product_variant_id']],
                ['harga_sale' => (int) $item['harga_sale'], 'kuota' => filled($item['kuota'] ?? null) ? (int) $item['kuota'] : null],
            );
        }
    }

    /**
     * @return list<array{id: int, label: string, harga: int, stok: int}>
     */
    private function variantOptions(): array
    {
        return ProductVariant::query()
            ->with('product:id,nama')
            ->withCount('availableSlots')
            ->where('is_aktif', true)
            ->orderBy('product_id')->orderBy('jenis_akun')->orderBy('durasi_hari')
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'label' => "{$variant->product->nama} — {$variant->jenis_akun->label()} {$variant->durasi_label}",
                'harga' => $variant->harga,
                'stok' => $variant->available_slots_count,
            ])
            ->all();
    }
}
