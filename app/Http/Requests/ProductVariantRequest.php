<?php

namespace App\Http\Requests;

use App\Enums\JenisAkun;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Dipakai untuk tambah & edit varian. Akses dibatasi middleware role:admin,karyawan.
 */
class ProductVariantRequest extends FormRequest
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
        $variant = $this->variant();
        $productId = $variant?->product_id ?? $this->product()?->id;

        return [
            'jenis_akun' => ['required', Rule::enum(JenisAkun::class)],
            'durasi_hari' => [
                'required', 'integer', 'min:1', 'max:3650',
                Rule::unique('product_variants', 'durasi_hari')
                    ->where('product_id', $productId)
                    ->where('jenis_akun', $this->input('jenis_akun'))
                    ->ignore($variant),
            ],
            'harga' => ['required', 'integer', 'min:0'],
            'harga_asli' => ['nullable', 'integer', 'gt:harga'],
            'harga_reseller' => ['nullable', 'integer', 'min:0', 'lte:harga'],
            'is_aktif' => ['boolean'],
        ];
    }

    /**
     * Tipe & durasi tidak boleh diubah kalau varian sudah punya stok,
     * karena stok private/sharing (jumlah slot) diinput sesuai tipe varian.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $variant = $this->variant();

                if (! $variant || $validator->errors()->hasAny(['jenis_akun', 'durasi_hari']) || ! $variant->stocks()->exists()) {
                    return;
                }

                if ($this->input('jenis_akun') !== $variant->jenis_akun->value) {
                    $validator->errors()->add('jenis_akun', 'Tipe akun tidak bisa diubah karena varian ini sudah punya stok.');
                }

                if ($this->integer('durasi_hari') !== $variant->durasi_hari) {
                    $validator->errors()->add('durasi_hari', 'Durasi tidak bisa diubah karena varian ini sudah punya stok.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'jenis_akun' => 'tipe akun',
            'durasi_hari' => 'durasi',
            'harga_asli' => 'harga coret',
            'harga_reseller' => 'harga reseller',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'durasi_hari.unique' => 'Varian dengan tipe & durasi ini sudah ada di produk ini.',
            'harga_asli.gt' => 'Harga coret harus lebih besar dari harga jual.',
            'harga_reseller.lte' => 'Harga reseller tidak boleh lebih mahal dari harga jual.',
        ];
    }

    private function variant(): ?ProductVariant
    {
        return $this->route('variant');
    }

    private function product(): ?Product
    {
        return $this->route('product');
    }
}
