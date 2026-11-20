<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Dipakai untuk tambah & edit produk. Akses dibatasi middleware role:admin,karyawan.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Slug kosong diisi otomatis dari nama.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('nama'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'garansi_hari' => ['required', 'integer', 'min:0', 'max:3650'],
            'is_aktif' => ['boolean'],
            'is_populer' => ['boolean'],
            // SVG sengaja tidak diterima karena bisa berisi skrip
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=64,min_height=64'],
            'hapus_logo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'garansi_hari' => 'garansi',
            'logo' => 'logo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug ini sudah dipakai produk lain. Ganti nama atau isi slug manual.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'logo.mimes' => 'Logo harus PNG, JPG, atau WEBP.',
            'logo.dimensions' => 'Logo minimal 64×64 piksel.',
        ];
    }
}
