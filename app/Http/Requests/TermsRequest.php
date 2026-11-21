<?php

namespace App\Http\Requests;

use App\Services\TermsContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Akses dibatasi middleware role:admin.
 */
class TermsRequest extends FormRequest
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
            'intro' => ['required', 'string', 'max:1000'],
            'bagian' => ['required', 'array', 'min:1', 'max:15'],
            'bagian.*.judul' => ['required', 'string', 'max:80'],
            'bagian.*.icon' => ['required', Rule::in(TermsContent::ICONS)],
            'bagian.*.isi' => ['required', 'string', 'max:3000'],
            'perbarui_tanggal' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bagian.*.judul' => 'judul bagian',
            'bagian.*.isi' => 'isi bagian',
            'bagian.*.icon' => 'ikon',
        ];
    }
}
