<?php

namespace App\Http\Requests;

use App\Services\StockImporter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockImportRequest extends FormRequest
{
    /**
     * Akses dibatasi middleware role:admin,karyawan di route.
     */
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
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'slot_default' => ['nullable', 'integer', 'min:2', 'max:'.StockImporter::MAX_SHARING_SLOT],
            'akun' => ['required', 'string', 'max:200000'],
            'aksi' => ['required', Rule::in(['preview', 'simpan'])],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('akun')) {
                    return;
                }

                $lineCount = count(array_filter(
                    preg_split('/\R/', (string) $this->input('akun')),
                    fn (string $line): bool => trim($line) !== '',
                ));

                if ($lineCount > StockImporter::MAX_LINES) {
                    $validator->errors()->add('akun', 'Maksimal '.StockImporter::MAX_LINES.' baris per input.');
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
            'product_variant_id' => 'varian produk',
            'slot_default' => 'slot default',
            'akun' => 'daftar akun',
        ];
    }
}
