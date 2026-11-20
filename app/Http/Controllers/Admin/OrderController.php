<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MetodeBayar;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'metode' => ['nullable', Rule::enum(MetodeBayar::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'perlu_cek' => ['nullable', 'boolean'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));

        $orders = Order::query()
            ->with(['user:id,name,email', 'items:id,order_id,snapshot_produk'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['metode'] ?? null, fn ($query, $metode) => $query->where('metode_bayar', $metode))
            ->when($request->boolean('perlu_cek'), fn ($query) => $query->where(fn ($q) => $q
                ->whereNotNull('late_paid_at')
                ->orWhere('catatan_admin', 'like', '%nominal berbeda%')))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('no_invoice', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => OrderPresenter::row($order));

        $counts = Order::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $filters['status'] ?? '',
                'metode' => $filters['metode'] ?? '',
                'q' => $search,
                'perlu_cek' => $request->boolean('perlu_cek'),
            ],
            'statusTabs' => collect(OrderStatus::cases())->map(fn (OrderStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'jumlah' => (int) ($counts[$status->value] ?? 0),
            ]),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['user:id,name,email,role', 'items.slot.stock']);
        $status = $order->status;

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                ...OrderPresenter::row($order),
                'pembeli_email' => $order->user->email,
                'pembeli_role' => $order->user->role->value,
                'metode_value' => $order->metode_bayar->value,
                'subtotal' => $order->subtotal,
                'kode_unik' => $order->kode_unik,
                'gateway_id' => $order->gateway_id,
                'expires_at' => $order->expires_at?->translatedFormat('d M Y, H:i'),
                'paid_at' => $order->paid_at?->translatedFormat('d M Y, H:i'),
                'late_paid_at' => $order->late_paid_at?->translatedFormat('d M Y, H:i'),
                'ada_bukti' => $order->bukti_bayar_path !== null,
                'alasan_tolak' => $order->alasan_tolak,
                'catatan_admin' => $order->catatan_admin,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'produk' => $item->snapshot_produk,
                    'varian' => "{$item->snapshot_jenis_akun->label()} · {$item->durasiLabel()}",
                    'harga' => $item->harga,
                    'terkirim' => $item->delivered_at?->translatedFormat('d M Y, H:i'),
                    'aktif_sampai' => $item->aktif_sampai?->translatedFormat('d M Y'),
                    'stock_id' => $item->slot?->stock_id,
                    'kredensial' => $item->credentials(),
                ]),
            ],
            'aksi' => [
                'verifikasi' => $order->metode_bayar === MetodeBayar::Manual && $status->isAwaitingPayment(),
                'tolak' => $status === OrderStatus::MenungguVerifikasi,
                'prosesBayarTelat' => $order->late_paid_at !== null && in_array($status, [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan], true),
                'kirimUlang' => $status === OrderStatus::Diproses,
                'batal' => $status->isAwaitingPayment() || $status === OrderStatus::Kedaluwarsa,
                'refund' => in_array($status, [OrderStatus::Diproses, OrderStatus::Selesai], true) || ($order->late_paid_at !== null && ! in_array($status, OrderStatus::paidStatuses(), true)),
            ],
        ]);
    }

    public function proof(Order $order): StreamedResponse
    {
        abort_unless($order->bukti_bayar_path && Storage::disk('local')->exists($order->bukti_bayar_path), 404);

        return Storage::disk('local')->response($order->bukti_bayar_path);
    }

    public function approve(Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $isManualAwaiting = $order->metode_bayar === MetodeBayar::Manual && $order->status->isAwaitingPayment();
        $isLatePaid = $order->late_paid_at !== null && in_array($order->status, [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan], true);

        if (! $isManualAwaiting && ! $isLatePaid) {
            return back()->with('error', 'Pesanan ini tidak bisa ditandai lunas dari panel.');
        }

        $order = $lifecycle->markPaid($order);

        return back()->with('success', $order->status === OrderStatus::Selesai
            ? 'Pembayaran diterima dan semua akun sudah terkirim ke pembeli.'
            : 'Pembayaran diterima, tetapi stok belum cukup. Pesanan akan dilengkapi otomatis saat stok ditambah.');
    }

    public function reject(Request $request, Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'max:255']], attributes: ['alasan' => 'alasan']);

        if ($order->status !== OrderStatus::MenungguVerifikasi) {
            return back()->with('error', 'Hanya bukti yang menunggu verifikasi yang bisa ditolak.');
        }

        $lifecycle->rejectProof($order, $data['alasan']);

        return back()->with('success', 'Bukti ditolak. Pembeli bisa mengunggah ulang.');
    }

    public function retry(Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $order = $lifecycle->fulfill($order);

        return back()->with(
            $order->status === OrderStatus::Selesai ? 'success' : 'error',
            $order->status === OrderStatus::Selesai ? 'Semua akun sudah terkirim.' : 'Stok masih kurang. Tambahkan stok untuk varian di pesanan ini.',
        );
    }

    public function cancel(Request $request, Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:255']], attributes: ['catatan' => 'alasan']);

        if (! $order->status->isAwaitingPayment() && $order->status !== OrderStatus::Kedaluwarsa) {
            return back()->with('error', 'Pesanan yang sudah dibayar tidak bisa dibatalkan. Gunakan refund.');
        }

        $lifecycle->cancel($order, 'Dibatalkan admin: '.$data['catatan']);

        return back()->with('success', 'Pesanan dibatalkan.');
    }

    public function refund(Request $request, Order $order, OrderLifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:255'],
            'ke_saldo' => ['boolean'],
        ], attributes: ['catatan' => 'catatan refund']);

        $canRefund = in_array($order->status, OrderStatus::paidStatuses(), true)
            || ($order->late_paid_at !== null && in_array($order->status, [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan], true));

        if (! $canRefund) {
            return back()->with('error', 'Hanya pesanan yang sudah dibayar yang bisa direfund.');
        }

        $toSaldo = $request->boolean('ke_saldo') || $order->metode_bayar === MetodeBayar::Saldo;
        $lifecycle->refund($order, $data['catatan'], $toSaldo);

        return back()->with('success', $toSaldo
            ? 'Pesanan direfund, dana dikembalikan ke saldo pembeli.'
            : 'Pesanan ditandai refund. Kembalikan dana ke pembeli di luar sistem.');
    }

    public function note(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['catatan_admin' => ['nullable', 'string', 'max:2000']]);

        $order->update(['catatan_admin' => $data['catatan_admin']]);

        return back()->with('success', 'Catatan disimpan.');
    }
}
