<?php

namespace App\Http\Requests;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Akses dibatasi middleware role:admin,karyawan.
 */
class FlashSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:80'],
            'mulai_at' => ['required', 'date'],
            'selesai_at' => ['required', 'date', 'after:mulai_at'],
            'is_aktif' => ['boolean'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct', Rule::exists('product_variants', 'id')],
            'items.*.harga_sale' => ['required', 'integer', 'min:0'],
            'items.*.kuota' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * Harga sale harus di bawah harga jual, dan satu varian tidak boleh ada di dua sale aktif yang jadwalnya bentrok.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $variants = ProductVariant::with('product:id,nama')->findMany(collect($this->input('items'))->pluck('product_variant_id'))->keyBy('id');
                foreach ($this->input('items') as $index => $item) {
                    $variant = $variants[$item['product_variant_id']];
                    if ((int) $item['harga_sale'] >= $variant->harga) {
                        $validator->errors()->add("items.{$index}.harga_sale", 'Harga sale harus lebih murah dari harga jual (Rp'.number_format($variant->harga, 0, ',', '.').').');
                    }
                }

                if (! $this->boolean('is_aktif')) {
                    return;
                }

                /** @var FlashSale|null $current */
                $current = $this->route('flashSale');
                $clashes = FlashSaleItem::query()
                    ->with(['flashSale', 'variant.product:id,nama'])
                    ->whereIn('product_variant_id', $variants->keys())
                    ->whereHas('flashSale', fn ($query) => $query
                        ->where('is_aktif', true)
                        ->when($current, fn ($q) => $q->whereKeyNot($current->id))
                        ->where('mulai_at', '<', $this->date('selesai_at'))
                        ->where('selesai_at', '>', $this->date('mulai_at')))
                    ->get();

                foreach ($clashes as $clash) {
                    $index = collect($this->input('items'))->search(fn ($item) => (int) $item['product_variant_id'] === $clash->product_variant_id);
                    $validator->errors()->add("items.{$index}.product_variant_id", "{$clash->variant->product->nama} sudah ada di \"{$clash->flashSale->nama}\" pada jam yang bentrok.");
                }
            },
        ];
    }

    /**
     * Input datetime-local dibaca sebagai zona waktu toko.
     */
    public function date($key, $format = null, $tz = null): ?Carbon
    {
        return parent::date($key, $format, $tz ?? config('app.timezone'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'mulai_at' => 'waktu mulai',
            'selesai_at' => 'waktu selesai',
            'items' => 'produk',
            'items.*.product_variant_id' => 'varian',
            'items.*.harga_sale' => 'harga sale',
            'items.*.kuota' => 'kuota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selesai_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'items.required' => 'Tambahkan minimal satu varian.',
            'items.*.product_variant_id.distinct' => 'Varian ini sudah dipilih di baris lain.',
        ];
    }
}
