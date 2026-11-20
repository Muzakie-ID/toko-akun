<?php

namespace App\Http\Controllers;

use App\Enums\MetodeBayar;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Orders\OrderCheckout;
use App\Services\Payment\PaymentGatewayException;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    private const MAX_QTY = 20;

    public function index(Request $request, Settings $settings): Response
    {
        $user = $request->user();

        $items = $user->cartItems()
            ->with(['variant' => fn ($query) => $query->withCount('availableSlots')->with('product')])
            ->oldest()
            ->get()
            ->map(function (CartItem $item) use ($user): array {
                $variant = $item->variant;
                $isAvailable = $variant->is_aktif && $variant->product->is_aktif;

                return [
                    'id' => $item->id,
                    'nama' => $variant->product->nama,
                    'slug' => $variant->product->slug,
                    'logo_url' => $variant->product->logoUrl(),
                    'jenis_akun_label' => $variant->jenis_akun->label(),
                    'durasi_label' => $variant->durasi_label,
                    'harga' => $variant->priceFor($user),
                    'qty' => $item->qty,
                    'stok' => $isAvailable ? $variant->available_slots_count : 0,
                ];
            });

        return Inertia::render('Cart', [
            'items' => $items,
            'metode' => array_values(array_filter([
                $settings->qrisReady() ? ['value' => MetodeBayar::Qris->value, 'label' => 'QRIS', 'keterangan' => 'Bayar dari e-wallet atau m-banking apa pun. Akun terkirim otomatis begitu pembayaran masuk.'] : null,
                $settings->manualReady() ? ['value' => MetodeBayar::Manual->value, 'label' => 'Transfer Bank', 'keterangan' => 'Transfer lalu unggah bukti. Akun dikirim setelah admin memverifikasi (maks. '.$settings->get('manual.batas_jam').' jam).'] : null,
            ])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where('is_aktif', true)],
            'qty' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_QTY],
            'beli_sekarang' => ['boolean'],
        ]);

        $variant = ProductVariant::withCount('availableSlots')->findOrFail($data['product_variant_id']);
        $item = $request->user()->cartItems()->firstOrNew(['product_variant_id' => $variant->id]);
        $qty = min(($item->exists ? $item->qty : 0) + ($data['qty'] ?? 1), self::MAX_QTY);

        if ($variant->available_slots_count < 1) {
            return back()->with('error', 'Stok varian ini sedang habis.');
        }

        $item->qty = min($qty, $variant->available_slots_count);
        $item->save();

        if ($request->boolean('beli_sekarang')) {
            return to_route('cart');
        }

        return back()->with('success', 'Ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 404);
        $data = $request->validate(['qty' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QTY]]);

        $item->update(['qty' => $data['qty']]);

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 404);

        $item->delete();

        return back();
    }

    public function checkout(Request $request, OrderCheckout $checkout): RedirectResponse
    {
        $data = $request->validate(['metode_bayar' => ['required', Rule::enum(MetodeBayar::class)]], [
            'metode_bayar.required' => 'Pilih metode pembayaran.',
        ]);
        $user = $request->user();
        $quantities = $user->cartItems()->pluck('qty', 'product_variant_id')->all();

        if ($quantities === []) {
            return back()->with('error', 'Keranjang masih kosong.');
        }

        try {
            $order = $checkout->checkout($user, $quantities, MetodeBayar::from($data['metode_bayar']));
        } catch (PaymentGatewayException) {
            return back()->with('error', 'Pembayaran QRIS sedang bermasalah. Coba lagi sebentar atau pilih transfer bank.');
        }

        return to_route('orders.show', $order);
    }
}
