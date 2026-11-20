<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 3;

    public function __invoke(Settings $settings): Response
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();
        // Bandingkan periode yang sama (mis. tgl 1–20 vs tgl 1–20 bulan lalu), bukan bulan berjalan vs bulan penuh
        $lastMonthSamePoint = $lastMonth->copy()->add($thisMonth->diff(now()))->min($thisMonth);

        $revenueThisMonth = $this->revenueBetween($thisMonth, now());
        $revenueLastMonth = $this->revenueBetween($lastMonth, $lastMonthSamePoint);
        $target = (int) $settings->get('store.target_bulanan');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                $this->stat('Pendapatan', $revenueThisMonth, $revenueLastMonth, 'rupiah', 'heroicons:banknotes'),
                $this->stat(
                    'Pesanan',
                    Order::where('created_at', '>=', $thisMonth)->count(),
                    Order::whereBetween('created_at', [$lastMonth, $lastMonthSamePoint])->count(),
                    'angka',
                    'heroicons:shopping-bag',
                ),
                $this->stat(
                    'Akun Terjual',
                    OrderItem::where('delivered_at', '>=', $thisMonth)->count(),
                    OrderItem::whereBetween('delivered_at', [$lastMonth, $lastMonthSamePoint])->count(),
                    'angka',
                    'heroicons:key',
                ),
                $this->stat(
                    'Pembeli Baru',
                    $this->buyers()->where('created_at', '>=', $thisMonth)->count(),
                    $this->buyers()->whereBetween('created_at', [$lastMonth, $lastMonthSamePoint])->count(),
                    'angka',
                    'heroicons:user-group',
                ),
            ],
            'chart' => [
                'bulanan' => $this->monthlyRevenue(),
                'harian' => $this->dailyRevenue(),
            ],
            'target' => [
                'tercapai' => $revenueThisMonth,
                'target' => $target,
                'persen' => $target > 0 ? round(min(100, $revenueThisMonth / $target * 100), 1) : 0,
            ],
            'perluTindakan' => [
                'verifikasi' => Order::where('status', OrderStatus::MenungguVerifikasi)->count(),
                'menungguStok' => Order::where('status', OrderStatus::Diproses)->count(),
                'bayarTelat' => Order::whereNotNull('late_paid_at')->whereIn('status', [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan])->count(),
            ],
            'pesananTerbaru' => Order::query()
                ->with(['user:id,name', 'items:id,order_id,snapshot_produk'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Order $order): array => OrderPresenter::row($order)),
            'stokMenipis' => ProductVariant::query()
                ->with('product:id,nama')
                ->where('is_aktif', true)
                ->whereHas('product', fn ($query) => $query->where('is_aktif', true))
                ->withCount('availableSlots')
                ->get()
                ->filter(fn (ProductVariant $variant) => $variant->available_slots_count <= self::LOW_STOCK_THRESHOLD)
                ->sortBy('available_slots_count')
                ->take(6)
                ->values()
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'produk' => $variant->product->nama,
                    'varian' => "{$variant->jenis_akun->label()} · {$variant->durasi_label}",
                    'tersedia' => $variant->available_slots_count,
                ]),
        ]);
    }

    private function revenueBetween(Carbon $from, Carbon $to): int
    {
        return (int) Order::whereIn('status', OrderStatus::paidStatuses())
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<', $to)
            ->sum('total');
    }

    /**
     * @return array{label: string, nilai: int, sebelumnya: int, format: string, icon: string, pertumbuhan: float|null}
     */
    private function stat(string $label, int $current, int $previous, string $format, string $icon): array
    {
        return [
            'label' => $label,
            'nilai' => $current,
            'sebelumnya' => $previous,
            'format' => $format,
            'icon' => $icon,
            'pertumbuhan' => $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null,
        ];
    }

    private function buyers(): Builder
    {
        return User::whereIn('role', [UserRole::Customer, UserRole::Reseller]);
    }

    /**
     * Pendapatan 12 bulan terakhir. Dikelompokkan di PHP agar sama di SQLite & MySQL.
     *
     * @return list<array{label: string, nilai: int}>
     */
    private function monthlyRevenue(): array
    {
        $start = now()->subMonthsNoOverflow(11)->startOfMonth();
        $totals = $this->paidOrdersSince($start)->groupBy(fn (Order $order) => $order->paid_at->format('Y-m'))->map->sum('total');

        return collect(range(0, 11))->map(function (int $offset) use ($start, $totals): array {
            $month = $start->copy()->addMonthsNoOverflow($offset);

            return ['label' => $month->translatedFormat('M'), 'nilai' => (int) ($totals[$month->format('Y-m')] ?? 0)];
        })->all();
    }

    /**
     * @return list<array{label: string, nilai: int}>
     */
    private function dailyRevenue(): array
    {
        $start = now()->subDays(13)->startOfDay();
        $totals = $this->paidOrdersSince($start)->groupBy(fn (Order $order) => $order->paid_at->format('Y-m-d'))->map->sum('total');

        return collect(range(0, 13))->map(function (int $offset) use ($start, $totals): array {
            $day = $start->copy()->addDays($offset);

            return ['label' => $day->format('d/m'), 'nilai' => (int) ($totals[$day->format('Y-m-d')] ?? 0)];
        })->all();
    }

    /**
     * @return Collection<int, Order>
     */
    private function paidOrdersSince(Carbon $start): Collection
    {
        return Order::query()
            ->whereIn('status', OrderStatus::paidStatuses())
            ->where('paid_at', '>=', $start)
            ->get(['id', 'total', 'paid_at']);
    }
}
