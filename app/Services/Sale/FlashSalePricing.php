<?php

namespace App\Services\Sale;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Menentukan item flash sale yang sedang berlaku per varian. Hasil disimpan per request agar katalog
 * tidak membuat query per varian; checkout selalu menghitung ulang kuota dengan lock.
 */
class FlashSalePricing
{
    /** @var Collection<int, FlashSaleItem>|null */
    private ?Collection $active = null;

    /**
     * Item sale yang berlaku untuk varian ini dan masih punya kuota, atau null.
     */
    public function activeItemFor(ProductVariant $variant): ?FlashSaleItem
    {
        $item = $this->activeItems()->get($variant->id);

        if (! $item || ($item->kuota !== null && $item->sisa($item->terpakai_items_count) === 0)) {
            return null;
        }

        return $item;
    }

    /**
     * Sale yang sedang berlangsung (paling cepat berakhir) untuk banner beranda.
     */
    public function currentSale(): ?FlashSale
    {
        return FlashSale::query()->berlangsung()->whereHas('items')->orderBy('selesai_at')->first();
    }

    public function flush(): void
    {
        $this->active = null;
    }

    /**
     * @return Collection<int, FlashSaleItem>
     */
    private function activeItems(): Collection
    {
        return $this->active ??= FlashSaleItem::query()
            ->with('flashSale')
            ->withCount('terpakaiItems')
            ->whereHas('flashSale', fn ($query) => $query->berlangsung())
            ->get()
            // Bila (seharusnya tidak terjadi) satu varian ada di dua sale aktif, ambil harga termurah
            ->sortBy('harga_sale')
            ->unique('product_variant_id')
            ->keyBy('product_variant_id');
    }
}
