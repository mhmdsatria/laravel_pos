<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Imports\ProductImport;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('nama_barang', 'like', '%' . $search . '%')
                        ->orWhere('kode_barang', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $nextKode = Product::generateNextKodeBarang();

        return view('pages.product', [
            'products' => $products,
            'search' => $search,
            'nextKode' => $nextKode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mode' => ['nullable', 'array'],
            'kode_mode.*' => ['nullable', Rule::in(['otomatis', 'manual'])],
            'kode_barang' => ['nullable', 'array'],
            'kode_barang.*' => ['nullable', 'string', 'max:100'],
            'nama_barang' => ['required', 'array', 'min:1'],
            'nama_barang.*' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'array'],
            'deskripsi.*' => ['nullable', 'string'],
            'satuan' => ['required', 'array', 'min:1'],
            'satuan.*' => ['required', 'string', 'max:100'],
            'harga_beli_terakhir' => ['required', 'array', 'min:1'],
            'harga_beli_terakhir.*' => ['required'],
            'harga_jual_normal' => ['nullable', 'array'],
            'harga_jual_normal.*' => ['nullable'],
            'stok_awal' => ['nullable', 'array'],
            'stok_awal.*' => ['nullable', 'numeric', 'min:0'],
            'sisa_stok' => ['nullable', 'array'],
            'sisa_stok.*' => ['nullable', 'numeric', 'min:0'],
            'limit_minimum_stok' => ['nullable', 'array'],
            'limit_minimum_stok.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = count($validated['nama_barang']);
        $manualCodes = [];

        for ($index = 0; $index < $rows; $index++) {
            $mode = $validated['kode_mode'][$index] ?? 'otomatis';
            $kodeBarang = trim((string) ($validated['kode_barang'][$index] ?? ''));

            if ($mode === 'manual') {
                if ($kodeBarang === '') {
                    throw ValidationException::withMessages([
                        'kode_barang.' . $index => 'Kode barang wajib diisi ketika mode manual dipilih.',
                    ]);
                }

                if (isset($manualCodes[$kodeBarang])) {
                    throw ValidationException::withMessages([
                        'kode_barang.' . $index => 'Kode barang manual tidak boleh duplikat dalam satu pengiriman data.',
                    ]);
                }

                if (Product::query()->where('kode_barang', $kodeBarang)->exists()) {
                    throw ValidationException::withMessages([
                        'kode_barang.' . $index => 'Kode barang ' . $kodeBarang . ' sudah digunakan.',
                    ]);
                }

                $manualCodes[$kodeBarang] = true;
            }
        }

        DB::transaction(function () use ($validated, $rows): void {
            for ($index = 0; $index < $rows; $index++) {
                $mode = $validated['kode_mode'][$index] ?? 'otomatis';
                $stokAwal = (float) str_replace(',', '.', (string) ($validated['stok_awal'][$index] ?? 0));
                $kodeBarang = $mode === 'manual' ? trim((string) ($validated['kode_barang'][$index] ?? '')) : null;

                Product::create([
                    'kode_barang' => $kodeBarang,
                    'nama_barang' => trim((string) $validated['nama_barang'][$index]),
                    'deskripsi' => $validated['deskripsi'][$index] ?? null,
                    'satuan' => trim((string) $validated['satuan'][$index]),
                    'harga_beli_terakhir' => $this->cleanCurrency($validated['harga_beli_terakhir'][$index]),
                    'harga_jual_normal' => $this->cleanCurrency($validated['harga_jual_normal'][$index] ?? $validated['harga_beli_terakhir'][$index] ?? 0),
                    'stok_awal' => $stokAwal,
                    'sisa_stok' => $stokAwal,
                    'limit_minimum_stok' => (float) str_replace(',', '.', (string) ($validated['limit_minimum_stok'][$index] ?? 0)),
                ]);
            }
        });

        return redirect()
            ->route('product.index')
            ->with('success', 'Data produk dan harga jual eceran berhasil disimpan ke database.');
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'kode_barang' => $product->kode_barang,
            'nama_barang' => $product->nama_barang,
            'deskripsi' => $product->deskripsi,
            'satuan' => $product->satuan,
            'harga_beli_terakhir' => (int) $product->harga_beli_terakhir,
            'harga_jual_normal' => (int) $product->harga_jual_normal,
            'stok_awal' => (float) $product->stok_awal,
            'sisa_stok' => (float) $product->sisa_stok,
            'limit_minimum_stok' => (float) $product->limit_minimum_stok,
            'created_at' => optional($product->created_at)->format('d M Y H:i'),
            'updated_at' => optional($product->updated_at)->format('d M Y H:i'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::query()->findOrFail($id);
        $oldKodeBarang = $product->kode_barang;

        $validated = $request->validate([
            'kode_barang_single' => ['required', 'string', 'max:100', Rule::unique('tbl_barang', 'kode_barang')->ignore($product->id)],
            'nama_barang_single' => ['required', 'string', 'max:255'],
            'deskripsi_single' => ['nullable', 'string'],
            'satuan_single' => ['required', 'string', 'max:100'],
            'harga_beli_terakhir_single' => ['required'],
            'harga_jual_normal_single' => ['nullable'],
            'stok_awal_single' => ['nullable', 'numeric', 'min:0'],
            'limit_minimum_stok_single' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($product, $validated, $oldKodeBarang): void {
            // Stok aktif merupakan saldo transaksi. Edit data master tidak boleh
            // mengosongkan atau menghitung ulang saldo tersebut secara diam-diam.
            $currentActiveStock = (float) $product->sisa_stok;
            $currentOpeningStock = (float) $product->stok_awal;
            $currentMinimumStock = (float) $product->limit_minimum_stok;
            $currentSellingPrice = (int) $product->harga_jual_normal;

            $product->update([
                'kode_barang' => trim((string) $validated['kode_barang_single']),
                'nama_barang' => trim((string) $validated['nama_barang_single']),
                'deskripsi' => $validated['deskripsi_single'] ?? null,
                'satuan' => trim((string) $validated['satuan_single']),
                'harga_beli_terakhir' => $this->cleanCurrency($validated['harga_beli_terakhir_single']),
                'harga_jual_normal' => array_key_exists('harga_jual_normal_single', $validated)
                    && trim((string) $validated['harga_jual_normal_single']) !== ''
                        ? $this->cleanCurrency($validated['harga_jual_normal_single'])
                        : $currentSellingPrice,
                'stok_awal' => array_key_exists('stok_awal_single', $validated)
                    && $validated['stok_awal_single'] !== null
                    && $validated['stok_awal_single'] !== ''
                        ? (float) str_replace(',', '.', (string) $validated['stok_awal_single'])
                        : $currentOpeningStock,
                'sisa_stok' => $currentActiveStock,
                'limit_minimum_stok' => array_key_exists('limit_minimum_stok_single', $validated)
                    && $validated['limit_minimum_stok_single'] !== null
                    && $validated['limit_minimum_stok_single'] !== ''
                        ? (float) str_replace(',', '.', (string) $validated['limit_minimum_stok_single'])
                        : $currentMinimumStock,
            ]);

            if ($oldKodeBarang !== $product->kode_barang && Schema::hasTable('stok_barang')) {
                DB::table('stok_barang')->where('kode_barang', $oldKodeBarang)->delete();
                $freshProduct = $product->fresh();
                if ($freshProduct instanceof Product) {
                    Product::syncStockRow($freshProduct);
                }
            }
        });

        return redirect()
            ->route('product.index')
            ->with('success', 'Data produk berhasil diperbarui. Stok aktif tetap dipertahankan sesuai saldo transaksi.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Data produk berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            Excel::import(new ProductImport, $request->file('excel_file'));
            
            return redirect()
                ->route('product.index')
                ->with('success', 'Seluruh data produk, stok awal, dan harga jual normal dari file Excel berhasil di-import.');
                
        } catch (\Throwable $e) {
            return redirect()
                ->route('product.index')
                ->withErrors(['excel_file' => 'Gagal memproses file Excel. Error: ' . $e->getMessage()]);
        }
    }

    private function cleanCurrency(mixed $value): int
    {
        $normalized = preg_replace('/[^0-9]/', '', (string) $value);

        return (int) ($normalized === '' ? 0 : $normalized);
    }
}