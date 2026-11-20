<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'varian' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['tersedia', 'habis'])],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $stocks = Stock::query()
            ->with(['variant.product:id,nama', 'slots.orderItem.order:id,no_invoice'])
            ->withCount(['slots as slot_terpakai' => fn ($query) => $query->whereNotNull('order_item_id')])
            ->when($filters['varian'] ?? null, fn ($query, $variantId) => $query->where('product_variant_id', $variantId))
            ->when(($filters['status'] ?? null) === 'tersedia', fn ($query) => $query->whereHas('slots', fn ($q) => $q->whereNull('order_item_id')))
            ->when(($filters['status'] ?? null) === 'habis', fn ($query) => $query->whereDoesntHave('slots', fn ($q) => $q->whereNull('order_item_id')))
            // Email terenkripsi, jadi pencarian memakai hash (harus email lengkap)
            ->when($filters['email'] ?? null, fn ($query, $email) => $query->where('email_hash', Stock::hashEmail($email)))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Stock $stock): array => [
                'id' => $stock->id,
                'produk' => $stock->variant->product->nama,
                'varian' => "{$stock->variant->jenis_akun->label()} · {$stock->variant->durasi_label}",
                'email' => $stock->email,
                'password' => $stock->password,
                'total_slot' => $stock->total_slot,
                'slot_terpakai' => $stock->slot_terpakai,
                'dibuat' => $stock->created_at->translatedFormat('d M Y'),
                'slots' => $stock->slots->map(fn (StockSlot $slot): array => [
                    'id' => $slot->id,
                    'nomor' => $slot->nomor,
                    'info' => $slot->info,
                    'invoice' => $slot->orderItem?->order?->no_invoice,
                ]),
            ]);

        return Inertia::render('Admin/Stocks/Index', [
            'stocks' => $stocks,
            'filters' => [
                'varian' => (string) ($filters['varian'] ?? ''),
                'status' => $filters['status'] ?? '',
                'email' => $filters['email'] ?? '',
            ],
            'variants' => ProductVariant::query()
                ->with('product:id,nama')
                ->orderBy('product_id')->orderBy('jenis_akun')->orderBy('durasi_hari')
                ->get()
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'label' => "{$variant->product->nama} — {$variant->jenis_akun->label()} {$variant->durasi_label}",
                ]),
        ]);
    }

    /**
     * Ganti kredensial (klaim garansi). Pembeli yang sudah memegang slot ini otomatis melihat kredensial baru.
     */
    public function update(Request $request, Stock $stock): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:255', 'not_regex:/\s/'],
            'password' => ['required', 'string', 'max:255'],
            'slots' => ['array'],
            'slots.*.id' => ['required', 'integer'],
            'slots.*.info' => ['nullable', 'string', 'max:255'],
        ], attributes: ['email' => 'email/username']);

        $duplicate = Stock::where('product_variant_id', $stock->product_variant_id)
            ->where('email_hash', Stock::hashEmail($data['email']))
            ->whereKeyNot($stock->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['email' => 'Akun ini sudah ada di stok varian yang sama.']);
        }

        DB::transaction(function () use ($stock, $data): void {
            $stock->update(['email' => $data['email'], 'password' => $data['password']]);

            $infoBySlot = collect($data['slots'] ?? [])->pluck('info', 'id');

            $stock->slots->each(function (StockSlot $slot) use ($infoBySlot): void {
                if ($infoBySlot->has($slot->id)) {
                    $slot->update(['info' => $infoBySlot[$slot->id]]);
                }
            });
        });

        return back()->with('success', 'Kredensial diperbarui. Pembeli langsung melihat data terbaru di halaman pesanannya.');
    }

    public function destroy(Stock $stock): RedirectResponse
    {
        if ($stock->slots()->whereNotNull('order_item_id')->exists()) {
            return back()->with('error', 'Stok ini sudah terjual ke pembeli, jadi tidak bisa dihapus.');
        }

        $stock->delete();

        return back()->with('success', 'Stok dihapus.');
    }
}
