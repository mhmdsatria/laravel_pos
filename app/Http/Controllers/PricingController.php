<?php

namespace App\Http\Controllers;

use App\Models\Pricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $tokoTargets = $this->getTokoTargets();
        $salesTargets = $this->getSalesTargets();
        $initialRows = [];

        if (Schema::hasTable('tbl_barang')) {
            $normalRows = $this->normalRows('');
            $specialRows = $this->allSpecialRows('');
            $initialRows = $normalRows->concat($specialRows)->values()->toArray();
        }

        return view('pages.pricing', [
            'tokoTargets' => $tokoTargets,
            'salesTargets' => $salesTargets,
            'initialRows' => $initialRows,
        ]);
    }

    public function filterData(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_barang')) {
            return response()->json([
                'success' => true,
                'category' => $request->query('category', 'all'),
                'rows' => [],
                'message' => 'Tabel tbl_barang belum tersedia. Silakan pasang modul Master Produk terlebih dahulu.',
            ]);
        }

        $category = strtolower((string) $request->query('category', 'all'));
        $targetId = (int) $request->query('target_id', 0);
        $search = trim((string) $request->query('q', ''));

        if ($category === 'normal') {
            $rows = $this->normalRows($search);
        } elseif ($category === 'toko' || $category === 'sales') {
            if ($targetId <= 0) {
                $rows = collect();
            } else {
                $rows = $this->specialRows($category === 'toko' ? 'Toko' : 'Sales', $targetId, $search);
            }
        } else {
            $normalRows = $this->normalRows($search);
            $specialRows = $this->allSpecialRows($search);
            $rows = $normalRows->concat($specialRows)->values();
        }

        return response()->json([
            'success' => true,
            'category' => $category,
            'rows' => $rows->values(),
        ]);
    }

    public function getModalProducts(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_barang')) {
            return response()->json([
                'success' => true,
                'rows' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                ],
                'message' => 'Tabel tbl_barang belum tersedia.',
            ]);
        }

        $category = strtolower((string) $request->query('category', 'normal'));
        $targetId = (int) $request->query('target_id', 0);
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(5, min($perPage, 50));
        $typeHarga = $category === 'toko' ? 'Toko' : ($category === 'sales' ? 'Sales' : null);

        $query = DB::table('tbl_barang')
            ->select($this->productSelectColumns());

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('nama_barang', 'like', '%' . $search . '%')
                    ->orWhere('kode_barang', 'like', '%' . $search . '%');
            });
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $pageProducts = $query
            ->orderBy('nama_barang')
            ->orderBy('kode_barang')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $existingSpecialPrices = collect();
        if ($typeHarga !== null && $targetId > 0 && Schema::hasTable('tbl_harga_khusus') && $pageProducts->isNotEmpty()) {
            $kodeBarangPage = $pageProducts->pluck('kode_barang')->filter()->values()->all();
            $existingSpecialPrices = Pricing::query()
                ->where('type_harga', $typeHarga)
                ->where('id_target', $targetId)
                ->whereIn('kode_barang', $kodeBarangPage)
                ->pluck('harga_khusus', 'kode_barang');
        }

        $rows = $pageProducts->map(function ($product) use ($category, $existingSpecialPrices) {
            $kodeBarang = (string) ($product->kode_barang ?? '');
            $hargaModal = (int) ($product->harga_beli_terakhir ?? 0);
            $hargaNormal = (int) ($product->harga_jual_normal ?? 0);
            $hargaKhusus = $existingSpecialPrices->has($kodeBarang) ? (int) $existingSpecialPrices->get($kodeBarang) : null;

            return [
                'kode_barang' => $kodeBarang,
                'nama_barang' => (string) ($product->nama_barang ?? ''),
                'harga_beli_terakhir' => $hargaModal,
                'harga_jual_normal' => $hargaNormal,
                'harga_khusus' => $hargaKhusus,
                'harga_aktif' => $category === 'normal' ? $hargaNormal : $hargaKhusus,
                'harga_acuan' => $category === 'normal' ? $hargaModal : $hargaNormal,
                'is_set' => $category === 'normal' ? $hargaNormal > 0 : $hargaKhusus !== null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total === 0 ? 0 : $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => ['required', 'in:normal,toko,sales'],
            'target_id' => ['nullable', 'integer'],
            'kode_barang' => ['required', 'array'],
            'kode_barang.*' => ['required', 'string'],
            'harga_baru' => ['required', 'array'],
            'changed' => ['required', 'array'],
        ]);

        if (! Schema::hasTable('tbl_barang')) {
            return redirect()->route('pricing.index')->with('error', 'Tabel produk belum tersedia. Pasang modul Master Produk terlebih dahulu.');
        }

        $category = strtolower((string) $request->input('category'));
        $targetId = (int) $request->input('target_id', 0);

        if (($category === 'toko' || $category === 'sales') && $targetId <= 0) {
            return redirect()->route('pricing.index')->with('error', 'Target harga khusus belum dipilih.');
        }

        $kodeBarangList = $request->input('kode_barang', []);
        $hargaBaruList = $request->input('harga_baru', []);
        $changedList = $request->input('changed', []);
        $processed = 0;

        DB::transaction(function () use ($category, $targetId, $kodeBarangList, $hargaBaruList, $changedList, &$processed): void {
            foreach ($kodeBarangList as $index => $kodeBarang) {
                $isChanged = (string) ($changedList[$index] ?? '0') === '1';
                if (! $isChanged) {
                    continue;
                }

                $kodeBarang = trim((string) $kodeBarang);
                $hargaRaw = (string) ($hargaBaruList[$index] ?? '');
                $hargaClean = preg_replace('/[^0-9]/', '', $hargaRaw) ?? '';

                if ($kodeBarang === '' || $hargaClean === '') {
                    continue;
                }

                $harga = (int) $hargaClean;

                if ($category === 'normal') {
                    DB::table('tbl_barang')
                        ->where('kode_barang', $kodeBarang)
                        ->update([
                            'harga_jual_normal' => $harga,
                            'updated_at' => now(),
                        ]);
                } else {
                    Pricing::query()->updateOrCreate(
                        [
                            'type_harga' => $category === 'toko' ? 'Toko' : 'Sales',
                            'id_target' => $targetId,
                            'kode_barang' => $kodeBarang,
                        ],
                        [
                            'harga_khusus' => $harga,
                        ]
                    );
                }

                $processed++;
            }
        });

        if ($processed === 0) {
            return redirect()->route('pricing.index')->with('error', 'Tidak ada baris harga yang diubah. Klik ikon edit pada baris produk sebelum menyimpan.');
        }

        return redirect()->route('pricing.index')->with('success', "Berhasil menyimpan {$processed} perubahan harga.");
    }


    public function downloadImportTemplate(Request $request): StreamedResponse
    {
        $request->validate([
            'category' => ['required', 'in:toko,sales'],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        if (! Schema::hasTable('tbl_barang')) {
            abort(422, 'Tabel produk belum tersedia.');
        }

        $category = strtolower((string) $request->query('category'));
        $targetId = (int) $request->query('target_id');
        $typeHarga = $category === 'toko' ? 'Toko' : 'Sales';

        if (! $this->targetExists($typeHarga, $targetId)) {
            abort(422, 'Target harga khusus tidak valid.');
        }

        $targetName = $this->targetName($typeHarga, $targetId);
        $filename = 'template-harga-khusus-' . strtolower($typeHarga) . '-' . $targetId . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($typeHarga, $targetId, $targetName): void {
            $handle = fopen('php://output', 'w');

            // BOM agar CSV terbaca rapi di Excel Windows.
            fwrite($handle, "\xEF\xBB\xBF");

            $delimiter = ';';
            fputcsv($handle, [
                'template_key',
                'type_harga',
                'id_target',
                'target_name',
                'kode_barang',
                'nama_barang',
                'harga_jual_normal',
                'harga_khusus_lama',
                'harga_khusus_baru',
            ], $delimiter);

            $products = DB::table('tbl_barang')
                ->select($this->productSelectColumns())
                ->orderBy('nama_barang')
                ->orderBy('kode_barang')
                ->get();

            $existingSpecialPrices = collect();
            if (Schema::hasTable('tbl_harga_khusus') && $products->isNotEmpty()) {
                $existingSpecialPrices = Pricing::query()
                    ->where('type_harga', $typeHarga)
                    ->where('id_target', $targetId)
                    ->whereIn('kode_barang', $products->pluck('kode_barang')->filter()->values()->all())
                    ->pluck('harga_khusus', 'kode_barang');
            }

            foreach ($products as $product) {
                $kodeBarang = (string) ($product->kode_barang ?? '');

                fputcsv($handle, [
                    'PRICING_SPECIAL_IMPORT_V1',
                    $typeHarga,
                    $targetId,
                    $targetName,
                    $kodeBarang,
                    (string) ($product->nama_barang ?? ''),
                    (int) ($product->harga_jual_normal ?? 0),
                    $existingSpecialPrices->has($kodeBarang) ? (int) $existingSpecialPrices->get($kodeBarang) : '',
                    '',
                ], $delimiter);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['required', 'in:toko,sales'],
            'target_id' => ['required', 'integer', 'min:1'],
            'template_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        if (! Schema::hasTable('tbl_barang')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel produk belum tersedia.',
            ], 422);
        }

        $category = strtolower((string) $request->input('category'));
        $targetId = (int) $request->input('target_id');
        $typeHarga = $category === 'toko' ? 'Toko' : 'Sales';

        if (! $this->targetExists($typeHarga, $targetId)) {
            return response()->json([
                'success' => false,
                'message' => 'Target harga khusus tidak valid.',
            ], 422);
        }

        $parsed = $this->parseCsvRows($request->file('template_file')->getRealPath());
        if (! empty($parsed['error'])) {
            return response()->json([
                'success' => false,
                'message' => $parsed['error'],
            ], 422);
        }

        $requiredHeaders = [
            'template_key',
            'type_harga',
            'id_target',
            'kode_barang',
            'harga_khusus_baru',
        ];
        $missingHeaders = array_values(array_diff($requiredHeaders, $parsed['headers']));

        if (! empty($missingHeaders)) {
            return response()->json([
                'success' => false,
                'message' => 'Format template tidak sesuai. Kolom hilang: ' . implode(', ', $missingHeaders),
            ], 422);
        }

        $rawRows = $parsed['rows'];
        if (empty($rawRows)) {
            return response()->json([
                'success' => false,
                'message' => 'Template kosong. Isi kolom harga_khusus_baru minimal satu barang.',
            ], 422);
        }

        $kodeBarangList = collect($rawRows)
            ->pluck('kode_barang')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = collect();
        if (! empty($kodeBarangList)) {
            $products = DB::table('tbl_barang')
                ->select($this->productSelectColumns())
                ->whereIn('kode_barang', $kodeBarangList)
                ->get()
                ->keyBy('kode_barang');
        }

        $existingSpecialPrices = collect();
        if (Schema::hasTable('tbl_harga_khusus') && ! empty($kodeBarangList)) {
            $existingSpecialPrices = Pricing::query()
                ->where('type_harga', $typeHarga)
                ->where('id_target', $targetId)
                ->whereIn('kode_barang', $kodeBarangList)
                ->pluck('harga_khusus', 'kode_barang');
        }

        $previewRows = [];
        $validRows = [];
        $seenKodeBarang = [];
        $validCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        foreach ($rawRows as $row) {
            $rowNumber = (int) ($row['_row_number'] ?? 0);
            $templateKey = trim((string) ($row['template_key'] ?? ''));
            $typeFromFile = trim((string) ($row['type_harga'] ?? ''));
            $targetFromFile = (int) trim((string) ($row['id_target'] ?? 0));
            $kodeBarang = trim((string) ($row['kode_barang'] ?? ''));
            $hargaRaw = trim((string) ($row['harga_khusus_baru'] ?? ''));
            $messages = [];
            $hargaBaru = null;

            if ($templateKey !== 'PRICING_SPECIAL_IMPORT_V1') {
                $messages[] = 'File bukan template import harga khusus dari sistem.';
            }

            if ($typeFromFile !== $typeHarga) {
                $messages[] = 'Kategori pada file tidak sama dengan pilihan halaman.';
            }

            if ($targetFromFile !== $targetId) {
                $messages[] = 'Target pada file tidak sama dengan target yang dipilih.';
            }

            if ($kodeBarang === '') {
                $messages[] = 'Kode barang kosong.';
            }

            if ($kodeBarang !== '') {
                if (isset($seenKodeBarang[$kodeBarang])) {
                    $messages[] = 'Kode barang duplikat di file.';
                }
                $seenKodeBarang[$kodeBarang] = true;
            }

            $product = $kodeBarang !== '' ? $products->get($kodeBarang) : null;
            if ($kodeBarang !== '' && ! $product) {
                $messages[] = 'Kode barang tidak ditemukan di tbl_barang.';
            }

            if ($hargaRaw === '') {
                $status = empty($messages) ? 'skip' : 'error';
                if ($status === 'skip') {
                    $messages[] = 'Harga khusus baru kosong, baris dilewati.';
                    $skipCount++;
                }
            } else {
                $hargaBaru = $this->normalizeImportPrice($hargaRaw);
                if ($hargaBaru === null || $hargaBaru <= 0) {
                    $messages[] = 'Harga khusus baru harus angka dan lebih dari 0.';
                }

                $status = empty($messages) ? 'valid' : 'error';
            }

            if ($status === 'valid') {
                $validRows[] = [
                    'kode_barang' => $kodeBarang,
                    'harga_khusus_baru' => $hargaBaru,
                ];
                $validCount++;
            } elseif ($status === 'error') {
                $errorCount++;
            }

            $hargaLama = $existingSpecialPrices->has($kodeBarang) ? (int) $existingSpecialPrices->get($kodeBarang) : null;

            $previewRows[] = [
                'row_number' => $rowNumber,
                'status' => $status,
                'kode_barang' => $kodeBarang,
                'nama_barang' => (string) ($product->nama_barang ?? ($row['nama_barang'] ?? '')),
                'harga_jual_normal' => (int) ($product->harga_jual_normal ?? 0),
                'harga_khusus_lama' => $hargaLama,
                'harga_khusus_baru' => $hargaBaru,
                'keterangan' => implode(' ', $messages),
            ];
        }

        $token = null;
        if ($validCount > 0) {
            $token = bin2hex(random_bytes(24));
            session()->put('pricing_import_' . $token, [
                'type_harga' => $typeHarga,
                'target_id' => $targetId,
                'target_name' => $this->targetName($typeHarga, $targetId),
                'rows' => $validRows,
                'created_at' => now()->timestamp,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preview import berhasil dibuat.',
            'token' => $token,
            'summary' => [
                'valid' => $validCount,
                'skip' => $skipCount,
                'error' => $errorCount,
                'total' => count($previewRows),
            ],
            'rows' => $previewRows,
        ]);
    }

    public function importExecute(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = (string) $request->input('token');
        $sessionKey = 'pricing_import_' . $token;
        $payload = session($sessionKey);

        if (! is_array($payload) || empty($payload['rows'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data preview import tidak ditemukan atau sudah kedaluwarsa. Upload ulang template.',
            ], 422);
        }

        $typeHarga = (string) ($payload['type_harga'] ?? '');
        $targetId = (int) ($payload['target_id'] ?? 0);
        $rows = $payload['rows'];
        $processed = 0;

        if (! in_array($typeHarga, ['Toko', 'Sales'], true) || $targetId <= 0 || ! $this->targetExists($typeHarga, $targetId)) {
            return response()->json([
                'success' => false,
                'message' => 'Target import tidak valid. Upload ulang template.',
            ], 422);
        }

        DB::transaction(function () use ($typeHarga, $targetId, $rows, &$processed): void {
            foreach ($rows as $row) {
                $kodeBarang = trim((string) ($row['kode_barang'] ?? ''));
                $hargaBaru = (int) ($row['harga_khusus_baru'] ?? 0);

                if ($kodeBarang === '' || $hargaBaru <= 0) {
                    continue;
                }

                Pricing::query()->updateOrCreate(
                    [
                        'type_harga' => $typeHarga,
                        'id_target' => $targetId,
                        'kode_barang' => $kodeBarang,
                    ],
                    [
                        'harga_khusus' => $hargaBaru,
                    ]
                );

                $processed++;
            }
        });

        session()->forget($sessionKey);

        return response()->json([
            'success' => true,
            'message' => "Berhasil import {$processed} harga khusus.",
            'processed' => $processed,
        ]);
    }

    private function normalRows(string $search): Collection
    {
        $query = DB::table('tbl_barang')->select($this->productSelectColumns());

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('nama_barang')
            ->orderBy('kode_barang')
            ->get()
            ->map(function ($product) {
                return [
                    'row_type' => 'normal',
                    'kategori_harga' => 'Normal',
                    'berlaku_untuk' => 'User / Normal',
                    'id_target' => null,
                    'kode_barang' => (string) ($product->kode_barang ?? ''),
                    'nama_barang' => (string) ($product->nama_barang ?? ''),
                    'harga_modal' => (int) ($product->harga_beli_terakhir ?? 0),
                    'harga_jual_normal' => (int) ($product->harga_jual_normal ?? 0),
                    'harga_khusus' => null,
                    'nominal_harga' => (int) ($product->harga_jual_normal ?? 0),
                ];
            });
    }

    private function specialRows(string $typeHarga, int $targetId, string $search): Collection
    {
        if (! Schema::hasTable('tbl_harga_khusus')) {
            return collect();
        }

        $targetName = $this->targetName($typeHarga, $targetId);

        $query = Pricing::query()
            ->with('product')
            ->where('type_harga', $typeHarga)
            ->where('id_target', $targetId);

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('kode_barang', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where('nama_barang', 'like', "%{$search}%")
                            ->orWhere('kode_barang', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderBy('kode_barang')
            ->get()
            ->map(function (Pricing $pricing) use ($targetName) {
                $product = $pricing->product;

                return [
                    'row_type' => strtolower($pricing->type_harga),
                    'kategori_harga' => $pricing->type_harga,
                    'berlaku_untuk' => $targetName,
                    'id_target' => (int) $pricing->id_target,
                    'kode_barang' => (string) $pricing->kode_barang,
                    'nama_barang' => (string) ($product->nama_barang ?? $pricing->kode_barang),
                    'harga_modal' => (int) ($product->harga_beli_terakhir ?? 0),
                    'harga_jual_normal' => (int) ($product->harga_jual_normal ?? 0),
                    'harga_khusus' => (int) $pricing->harga_khusus,
                    'nominal_harga' => (int) $pricing->harga_khusus,
                ];
            });
    }

    private function allSpecialRows(string $search): Collection
    {
        if (! Schema::hasTable('tbl_harga_khusus')) {
            return collect();
        }

        $tokoMap = Schema::hasTable('tbl_customer') ? DB::table('tbl_customer')->pluck('nama_pelanggan', 'id')->toArray() : [];
        $salesMap = Schema::hasTable('tbl_sales') ? DB::table('tbl_sales')->pluck('nama_sales', 'id')->toArray() : [];

        $query = Pricing::query()->with('product');

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('kode_barang', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where('nama_barang', 'like', "%{$search}%")
                            ->orWhere('kode_barang', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderBy('type_harga')
            ->orderBy('id_target')
            ->orderBy('kode_barang')
            ->get()
            ->map(function (Pricing $pricing) use ($tokoMap, $salesMap) {
                $product = $pricing->product;
                $targetId = (int) $pricing->id_target;
                $typeHarga = (string) $pricing->type_harga;

                if ($typeHarga === 'Toko') {
                    $targetName = $tokoMap[$targetId] ?? "Toko ID {$targetId}";
                } elseif ($typeHarga === 'Sales') {
                    $targetName = $salesMap[$targetId] ?? "Sales ID {$targetId}";
                } else {
                    $targetName = "Target ID {$targetId}";
                }

                return [
                    'row_type' => strtolower($typeHarga),
                    'kategori_harga' => $typeHarga,
                    'berlaku_untuk' => $targetName,
                    'id_target' => $targetId,
                    'kode_barang' => (string) $pricing->kode_barang,
                    'nama_barang' => (string) ($product->nama_barang ?? $pricing->kode_barang),
                    'harga_modal' => (int) ($product->harga_beli_terakhir ?? 0),
                    'harga_jual_normal' => (int) ($product->harga_jual_normal ?? 0),
                    'harga_khusus' => (int) $pricing->harga_khusus,
                    'nominal_harga' => (int) $pricing->harga_khusus,
                ];
            });
    }

    private function productSelectColumns(): array
    {
        $columns = ['kode_barang', 'nama_barang'];
        $columns[] = Schema::hasColumn('tbl_barang', 'harga_beli_terakhir') ? 'harga_beli_terakhir' : DB::raw('0 as harga_beli_terakhir');
        $columns[] = Schema::hasColumn('tbl_barang', 'harga_jual_normal') ? 'harga_jual_normal' : DB::raw('0 as harga_jual_normal');

        return $columns;
    }

    private function getTokoTargets(): Collection
    {
        if (! Schema::hasTable('tbl_customer')) {
            return collect();
        }

        $query = DB::table('tbl_customer')->select('id', DB::raw('nama_pelanggan as name'));

        if (Schema::hasColumn('tbl_customer', 'kategori')) {
            $query->where('kategori', 'Toko');
        }

        return $query->orderBy('nama_pelanggan')->get()->map(function ($target) {
            return [
                'id' => (int) $target->id,
                'name' => (string) $target->name,
            ];
        });
    }

    private function getSalesTargets(): Collection
    {
        if (! Schema::hasTable('tbl_sales')) {
            return collect();
        }

        $query = DB::table('tbl_sales')->select('id', DB::raw('nama_sales as name'));

        if (Schema::hasColumn('tbl_sales', 'status')) {
            $query->where('status', 'Aktif');
        }

        return $query->orderBy('nama_sales')->get()->map(function ($target) {
            return [
                'id' => (int) $target->id,
                'name' => (string) $target->name,
            ];
        });
    }


    private function parseCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [
                'error' => 'File tidak bisa dibaca.',
                'headers' => [],
                'rows' => [],
            ];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [
                'error' => 'File kosong.',
                'headers' => [],
                'rows' => [],
            ];
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if (! is_array($rawHeaders) || empty($rawHeaders)) {
            fclose($handle);
            return [
                'error' => 'Header CSV tidak terbaca.',
                'headers' => [],
                'rows' => [],
            ];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeaderName((string) $header), $rawHeaders);
        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            $isBlank = true;
            foreach ($data as $value) {
                if (trim((string) $value) !== '') {
                    $isBlank = false;
                    break;
                }
            }

            if ($isBlank) {
                continue;
            }

            $row = [
                '_row_number' => $lineNumber,
            ];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $data[$index] ?? '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'error' => null,
            'headers' => array_values(array_filter($headers)),
            'rows' => $rows,
        ];
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);
        $delimiter = array_key_first($delimiters);

        return $delimiters[$delimiter] > 0 ? $delimiter : ';';
    }

    private function normalizeHeaderName(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = trim(strtolower($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    private function normalizeImportPrice(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $withoutCurrencyText = preg_replace('/\b(rp|idr)\b/i', '', $raw) ?? $raw;
        if (preg_match('/[a-zA-Z]/', $withoutCurrencyText)) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $raw) ?? '';
        if ($clean === '') {
            return null;
        }

        return (int) $clean;
    }

    private function targetExists(string $typeHarga, int $targetId): bool
    {
        if ($targetId <= 0) {
            return false;
        }

        if ($typeHarga === 'Toko') {
            return Schema::hasTable('tbl_customer') && DB::table('tbl_customer')->where('id', $targetId)->exists();
        }

        if ($typeHarga === 'Sales') {
            return Schema::hasTable('tbl_sales') && DB::table('tbl_sales')->where('id', $targetId)->exists();
        }

        return false;
    }

    private function targetName(string $typeHarga, int $targetId): string
    {
        if ($typeHarga === 'Toko' && Schema::hasTable('tbl_customer')) {
            $name = DB::table('tbl_customer')->where('id', $targetId)->value('nama_pelanggan');
            return $name ? (string) $name : "Toko ID {$targetId}";
        }

        if ($typeHarga === 'Sales' && Schema::hasTable('tbl_sales')) {
            $name = DB::table('tbl_sales')->where('id', $targetId)->value('nama_sales');
            return $name ? (string) $name : "Sales ID {$targetId}";
        }

        return "Target ID {$targetId}";
    }
}
