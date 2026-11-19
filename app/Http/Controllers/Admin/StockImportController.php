<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockImportRequest;
use App\Models\ProductVariant;
use App\Services\StockImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockImportController extends Controller
{
    public function create(Request $request): Response
    {
        $variants = ProductVariant::query()
            ->with('product:id,nama')
            ->withCount('stocks')
            ->orderBy('product_id')
            ->orderBy('jenis_akun')
            ->orderBy('durasi_hari')
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'produk' => $variant->product->nama,
                'jenis_akun' => $variant->jenis_akun->value,
                'jenis_akun_label' => $variant->jenis_akun->label(),
                'durasi_label' => $variant->durasi_label,
                'jumlah_stok' => $variant->stocks_count,
            ]);

        return Inertia::render('Admin/StockImport', [
            'variants' => $variants,
            'preview' => $request->session()->get('import_preview'),
            'result' => $request->session()->get('import_result'),
        ]);
    }

    public function store(StockImportRequest $request, StockImporter $importer): RedirectResponse
    {
        $variant = ProductVariant::findOrFail($request->integer('product_variant_id'));
        $slotDefault = $request->filled('slot_default') ? $request->integer('slot_default') : null;

        $rows = $importer->parse($request->string('akun')->toString(), $variant, $slotDefault);

        if ($request->input('aksi') === 'preview') {
            // Password tidak ikut disimpan ke session; textarea tetap dipegang di form klien
            $preview = array_map(fn (array $row): array => array_diff_key($row, ['password' => true]), $rows);

            return back()->with('import_preview', $preview);
        }

        $savedCount = $importer->import($rows, $variant, $request->user());
        $rejected = array_values(array_filter($rows, fn (array $row): bool => $row['errors'] !== []));

        return back()->with('import_result', [
            'tersimpan' => $savedCount,
            'ditolak' => array_map(fn (array $row): array => [
                'baris' => $row['baris'],
                'email' => $row['email'],
                'errors' => $row['errors'],
            ], $rejected),
        ]);
    }
}
