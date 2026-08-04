<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasePayment;
use App\Services\DesktopBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $query = DB::table('tbl_pembelian')
            ->leftJoin('tbl_supplier', 'tbl_pembelian.supplier_id', '=', 'tbl_supplier.id')
            ->select([
                'tbl_pembelian.id',
                'tbl_pembelian.no_invoice',
                'tbl_pembelian.tgl_pembelian',
                'tbl_pembelian.supplier_id',
                'tbl_pembelian.total_item',
                'tbl_pembelian.total_harga',
                'tbl_pembelian.paid_total',
                'tbl_pembelian.remaining_total',
                'tbl_pembelian.payment_status',
                'tbl_pembelian.status',
                'tbl_pembelian.note',
                'tbl_pembelian.created_at',
                'tbl_supplier.nama_supplier',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('tbl_pembelian.no_invoice', 'like', '%' . $search . '%')
                    ->orWhere('tbl_supplier.nama_supplier', 'like', '%' . $search . '%')
                    ->orWhere('tbl_supplier.alamat', 'like', '%' . $search . '%')
                    ->orWhere('tbl_supplier.nama_pic', 'like', '%' . $search . '%')
                    ->orWhere('tbl_pembelian.note', 'like', '%' . $search . '%')
                    ->orWhere('tbl_pembelian.status', 'like', '%' . $search . '%')
                    ->orWhere('tbl_pembelian.payment_status', 'like', '%' . $search . '%')
                    ->orWhereExists(function ($detailQuery) use ($search): void {
                        $detailQuery->select(DB::raw(1))
                            ->from('tbl_pembelian_detail')
                            ->leftJoin('tbl_barang', 'tbl_barang.kode_barang', '=', 'tbl_pembelian_detail.kode_barang')
                            ->whereColumn('tbl_pembelian_detail.pembelian_id', 'tbl_pembelian.id')
                            ->where(function ($itemQuery) use ($search): void {
                                $itemQuery->where('tbl_pembelian_detail.kode_barang', 'like', '%' . $search . '%')
                                    ->orWhere('tbl_barang.nama_barang', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $purchaseSummary = $this->buildPurchaseIndexSummary(clone $query);
        $purchases = $query->orderByDesc('tbl_pembelian.tgl_pembelian')->orderByDesc('tbl_pembelian.id')->paginate(10)->withQueryString();
        $suppliers = Schema::hasTable('tbl_supplier') ? DB::table('tbl_supplier')->orderBy('nama_supplier')->get(['id', 'nama_supplier', 'nama_pic', 'no_hp']) : collect();
        $products = Schema::hasTable('tbl_barang') ? DB::table('tbl_barang')->orderBy('nama_barang')->get(['kode_barang', 'nama_barang', 'satuan', 'harga_beli_terakhir', 'sisa_stok']) : collect();
        $nextInvoice = Purchase::generateNextInvoice();

        return view('pages.purchase', compact('purchases', 'suppliers', 'products', 'nextInvoice', 'search', 'purchaseSummary'));
    }

    private function buildPurchaseIndexSummary($query): array
    {
        $summary = [
            'total_nota' => (int) (clone $query)->count('tbl_pembelian.id'),
            'total_qty' => 0,
            'total_baris_item' => 0,
            'total_harga' => (int) (clone $query)->sum('tbl_pembelian.total_harga'),
            'total_dibayar' => (int) (clone $query)->sum('tbl_pembelian.paid_total'),
            'total_sisa' => (int) (clone $query)->sum('tbl_pembelian.remaining_total'),
        ];

        if ($summary['total_nota'] <= 0 || ! Schema::hasTable('tbl_pembelian_detail')) {
            return $summary;
        }

        $idSubQuery = (clone $query)->select('tbl_pembelian.id');
        $detailSummary = DB::table('tbl_pembelian_detail')
            ->whereIn('pembelian_id', $idSubQuery)
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COUNT(id) as total_baris_item')
            ->first();

        $summary['total_qty'] = (int) ($detailSummary->total_qty ?? 0);
        $summary['total_baris_item'] = (int) ($detailSummary->total_baris_item ?? 0);

        return $summary;
    }

    public function show(int $id): JsonResponse
    {
        $purchase = Purchase::query()
            ->with(['supplier', 'details.product:kode_barang,nama_barang,satuan', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->purchasePayload($purchase),
        ]);
    }

    public function invoice(int $id): View
    {
        $purchase = Purchase::query()
            ->with(['supplier', 'details.product:kode_barang,nama_barang,satuan', 'payments'])
            ->findOrFail($id);

        return view('pages.purchase-invoice', compact('purchase'));
    }

    public function exportExcel(Request $request, DesktopBridge $desktop)
    {
        try {
            $search = trim((string) $request->query('search', ''));
            $purchases = $this->purchaseExportData($search);
            $filename = 'Pembelian_Restock_' . now()->format('Ymd_His') . '.xls';
            $html = $this->buildPurchaseExcelHtml($purchases, $search);

            if ($desktop->shouldUseNativeDialogs($request)) {
                $settings = $this->settingsMap();
                $folder = $desktop->selectExportFolder(
                    'Pilih Folder Simpan Excel Pembelian / Restock',
                    $settings['data_directory'] ?? null
                );

                if (is_string($folder) && trim($folder) !== '') {
                    $folder = rtrim($folder, DIRECTORY_SEPARATOR);
                    if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
                        throw new \RuntimeException('Folder export tidak dapat dibuat: ' . $folder);
                    }

                    $targetPath = $folder . DIRECTORY_SEPARATOR . $filename;
                    file_put_contents($targetPath, $html);
                    $this->saveSetting('data_directory', $folder);

                    return redirect()
                        ->route('purchase.index', $request->query())
                        ->with('success', 'Excel pembelian/restock berhasil disimpan: ' . $targetPath);
                }
            }

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('purchase.index', $request->query())
                ->withErrors(['export_excel' => 'Gagal mengekspor Excel pembelian/restock: ' . $exception->getMessage()]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePurchasePayload($request, true);

        try {
            DB::transaction(function () use ($validated): void {
                $items = $this->normalizeItems($validated['kode_barang'], $validated['qty'], $validated['harga_beli']);
                if ($items === []) {
                    throw new \RuntimeException('Tidak ada item pembelian yang valid untuk disimpan.');
                }

                [$totalQty, $totalHarga, $totalItemText] = $this->buildTotals($items);
                $initialPayment = $this->cleanNumber($validated['initial_payment'] ?? 0);

                if ($initialPayment > $totalHarga) {
                    throw new \RuntimeException('Pembayaran awal tidak boleh melebihi total pembelian.');
                }

                $purchase = Purchase::query()->create([
                    'tgl_pembelian' => $validated['tgl_pembelian'],
                    'supplier_id' => (int) $validated['supplier_id'],
                    'total_item' => $totalItemText,
                    'total_harga' => $totalHarga,
                    'paid_total' => 0,
                    'remaining_total' => $totalHarga,
                    'payment_status' => 'Belum Dibayar',
                    'status' => 'Selesai',
                    'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                ]);

                $this->insertPurchaseDetails($purchase->id, $items);

                foreach ($items as $item) {
                    $this->applyStockDelta($item['kode_barang'], (float) $item['qty'], (int) $item['harga_beli']);
                }

                if ($initialPayment > 0) {
                    PurchasePayment::query()->create([
                        'pembelian_id' => $purchase->id,
                        'payment_date' => $validated['initial_payment_date'] ?? now()->toDateString(),
                        'amount' => $initialPayment,
                        'payment_method' => trim((string) ($validated['initial_payment_method'] ?? 'Tunai')) ?: 'Tunai',
                        'payment_note' => trim((string) ($validated['initial_payment_note'] ?? '')) ?: null,
                        'created_by' => auth()->id(),
                    ]);
                }

                $this->refreshPaymentSummary($purchase->id);
            }, 3);

            return redirect()->route('purchase.index')->with('success', 'Nota pembelian berhasil disimpan, stok diperbarui, dan status pembayaran sudah dihitung otomatis.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->back()->withInput()->withErrors(['purchase_error' => 'Gagal menyimpan nota pembelian: ' . $exception->getMessage()]);
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $this->validatePurchasePayload($request, false);

        try {
            DB::transaction(function () use ($id, $validated): void {
                $purchase = Purchase::query()->lockForUpdate()->findOrFail($id);
                $oldDetails = PurchaseDetail::query()->where('pembelian_id', $purchase->id)->lockForUpdate()->get();
                $items = $this->normalizeItems($validated['kode_barang'], $validated['qty'], $validated['harga_beli']);

                if ($items === []) {
                    throw new \RuntimeException('Tidak ada item pembelian yang valid untuk diperbarui.');
                }

                // Koreksi stok berdasarkan SELISIH qty, bukan membalik seluruh stok lama.
                // Ini mencegah stok melonjak saat sebagian barang dari nota lama sudah terjual.
                $oldQtyByCode = $oldDetails
                    ->groupBy('kode_barang')
                    ->map(fn ($rows): float => (float) $rows->sum('qty'));
                $newQtyByCode = collect($items)
                    ->groupBy('kode_barang')
                    ->map(fn ($rows): float => (float) collect($rows)->sum('qty'));
                $newPriceByCode = collect($items)
                    ->groupBy('kode_barang')
                    ->map(fn ($rows): int => (int) collect($rows)->last()['harga_beli']);

                PurchaseDetail::query()->where('pembelian_id', $purchaseId ?? $purchase->id)->delete();
                $this->insertPurchaseDetails($purchase->id, $items);

                $allCodes = $oldQtyByCode->keys()->merge($newQtyByCode->keys())->unique()->values();
                foreach ($allCodes as $code) {
                    $delta = (float) $newQtyByCode->get($code, 0) - (float) $oldQtyByCode->get($code, 0);
                    $latestPrice = $newPriceByCode->has($code) ? (int) $newPriceByCode->get($code) : null;
                    $this->applyStockDelta((string) $code, $delta, $latestPrice);
                }

                [$totalQty, $totalHarga, $totalItemText] = $this->buildTotals($items);
                $purchase->update([
                    'tgl_pembelian' => $validated['tgl_pembelian'],
                    'supplier_id' => (int) $validated['supplier_id'],
                    'total_item' => $totalItemText,
                    'total_harga' => $totalHarga,
                    'status' => 'Selesai',
                    'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                ]);

                $this->refreshPaymentSummary($purchase->id);
            }, 3);

            return redirect()->route('purchase.index')->with('success', 'Nota pembelian berhasil diperbarui. Stok dan status pembayaran sudah disesuaikan otomatis.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->back()->withInput()->withErrors(['purchase_error' => 'Gagal memperbarui nota pembelian: ' . $exception->getMessage()]);
        }
    }

    public function storePayment(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
        ]);

        try {
            DB::transaction(function () use ($id, $validated): void {
                $purchase = Purchase::query()->lockForUpdate()->findOrFail($id);
                $this->refreshPaymentSummary($purchase->id);
                $purchase->refresh();

                $amount = $this->cleanNumber($validated['amount']);
                if ($amount <= 0) {
                    throw new \RuntimeException('Nominal pembayaran harus lebih dari 0.');
                }

                $remaining = max(0, (int) $purchase->remaining_total);
                if ($remaining <= 0) {
                    throw new \RuntimeException('Nota pembelian ini sudah lunas.');
                }

                if ($amount > $remaining) {
                    throw new \RuntimeException('Nominal pembayaran melebihi sisa hutang. Sisa hutang saat ini Rp ' . number_format($remaining, 0, ',', '.') . '.');
                }

                PurchasePayment::query()->create([
                    'pembelian_id' => $purchase->id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $amount,
                    'payment_method' => trim((string) ($validated['payment_method'] ?? 'Tunai')) ?: 'Tunai',
                    'payment_note' => trim((string) ($validated['payment_note'] ?? '')) ?: null,
                    'created_by' => auth()->id(),
                ]);

                $this->refreshPaymentSummary($purchase->id);
            }, 3);

            return redirect()->route('purchase.index')->with('success', 'Pembayaran pembelian berhasil dicatat.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->back()->withErrors(['purchase_error' => 'Gagal menyimpan pembayaran: ' . $exception->getMessage()]);
        }
    }

    private function validatePurchasePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'tgl_pembelian' => ['required', 'date'],
            'supplier_id' => ['required', 'integer'],
            'kode_barang' => ['required', 'array', 'min:1'],
            'kode_barang.*' => ['required', 'string'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required'],
            'harga_beli' => ['required', 'array', 'min:1'],
            'harga_beli.*' => ['required'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($isCreate) {
            $rules['initial_payment'] = ['nullable'];
            $rules['initial_payment_date'] = ['nullable', 'date'];
            $rules['initial_payment_method'] = ['nullable', 'string', 'max:100'];
            $rules['initial_payment_note'] = ['nullable', 'string', 'max:1000'];
        }

        return $request->validate($rules, [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'kode_barang.required' => 'Minimal satu barang wajib dimasukkan.',
            'qty.*.required' => 'Qty barang wajib diisi.',
            'harga_beli.*.required' => 'Harga beli wajib diisi.',
        ]);
    }

    private function normalizeItems(array $kodeBarang, array $qtyList, array $hargaBeliList): array
    {
        $rawItems = [];
        $codes = [];

        foreach ($kodeBarang as $index => $kode) {
            $kode = trim((string) $kode);
            $qty = $this->cleanQty($qtyList[$index] ?? 0);
            $hargaBeli = $this->cleanNumber($hargaBeliList[$index] ?? 0);

            if ($kode === '' || $qty <= 0 || $hargaBeli < 0) {
                continue;
            }

            $rawItems[] = [
                'kode_barang' => $kode,
                'qty' => $qty,
                'harga_beli' => $hargaBeli,
                'subtotal' => (int) round((float) $qty * (float) $hargaBeli),
            ];
            $codes[] = $kode;
        }

        if ($rawItems === []) {
            return [];
        }

        $products = DB::table('tbl_barang')->whereIn('kode_barang', array_values(array_unique($codes)))->get(['kode_barang'])->keyBy('kode_barang');
        foreach ($rawItems as $item) {
            if (! $products->has($item['kode_barang'])) {
                throw new \RuntimeException('Barang dengan kode ' . $item['kode_barang'] . ' tidak ditemukan.');
            }
        }

        return $rawItems;
    }

    private function buildTotals(array $items): array
    {
        $totalQty = array_sum(array_column($items, 'qty'));
        $totalHarga = array_sum(array_column($items, 'subtotal'));

        return [$totalQty, $totalHarga, count($items) . ' Jenis / ' . $totalQty . ' Qty'];
    }

    private function insertPurchaseDetails(int $purchaseId, array $items): void
    {
        $now = now();
        $rows = array_map(fn (array $item): array => [
            'pembelian_id' => $purchaseId,
            'kode_barang' => $item['kode_barang'],
            'qty' => $item['qty'],
            'harga_beli' => $item['harga_beli'],
            'subtotal' => $item['subtotal'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $items);

        PurchaseDetail::query()->insert($rows);
    }

    private function cleanNumber(mixed $value): int
    {
        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);
        return $cleaned === null || $cleaned === '' ? 0 : (int) $cleaned;
    }

    private function cleanQty(mixed $value): float
    {
        if ($value === null || $value === '') return 0.0;
        $str = str_replace(',', '.', (string) $value);
        $cleaned = preg_replace('/[^0-9.]/', '', $str);
        return $cleaned === '' ? 0.0 : (float) $cleaned;
    }

    private function applyStockDelta(string $kodeBarang, float $deltaQty, ?int $hargaBeli): void
    {
        $product = DB::table('tbl_barang')->where('kode_barang', $kodeBarang)->lockForUpdate()->first();
        if (! $product) {
            throw new \RuntimeException('Barang dengan kode ' . $kodeBarang . ' tidak ditemukan saat penyesuaian stok.');
        }

        $currentStock = (float) ($product->sisa_stok ?? 0);
        $newStock = $currentStock + $deltaQty;

        if ($newStock < 0) {
            throw new \RuntimeException(
                'Koreksi nota membuat stok ' . ($product->nama_barang ?? $kodeBarang) .
                ' menjadi negatif. Stok aktif saat ini ' . $currentStock .
                ', perubahan ' . $deltaQty . '. Sesuaikan qty nota atau lakukan adjustment stok terlebih dahulu.'
            );
        }

        $payload = ['sisa_stok' => $newStock];
        if ($hargaBeli !== null && $hargaBeli > 0) {
            // Langsung timpa HPP / harga modal barang dengan harga pembelian terbaru dari supplier
            $payload['harga_beli_terakhir'] = (int) $hargaBeli;
        }
        if (Schema::hasColumn('tbl_barang', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('tbl_barang')->where('kode_barang', $kodeBarang)->update($payload);
        $this->syncStokBarang($kodeBarang);
    }

    private function syncStokBarang(string $kodeBarang): void
    {
        if (! Schema::hasTable('stok_barang')) {
            return;
        }

        $product = DB::table('tbl_barang')->where('kode_barang', $kodeBarang)->first();
        if (! $product) {
            return;
        }

        $payload = [
            'nama_barang' => $product->nama_barang ?? '',
            'stok_aktif' => (float) ($product->sisa_stok ?? 0),
        ];

        if (Schema::hasColumn('stok_barang', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $exists = DB::table('stok_barang')->where('kode_barang', $kodeBarang)->exists();
        if ($exists) {
            DB::table('stok_barang')->where('kode_barang', $kodeBarang)->update($payload);
            return;
        }

        $payload['kode_barang'] = $kodeBarang;
        if (Schema::hasColumn('stok_barang', 'created_at')) {
            $payload['created_at'] = now();
        }

        DB::table('stok_barang')->insert($payload);
    }

    private function refreshPaymentSummary(int $purchaseId): void
    {
        $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchaseId);
        $paidTotal = (int) PurchasePayment::query()->where('pembelian_id', $purchase->id)->sum('amount');
        $totalHarga = (int) $purchase->total_harga;
        $remaining = max(0, $totalHarga - $paidTotal);
        $paymentStatus = $this->calculatePaymentStatus($totalHarga, $paidTotal);

        $purchase->update([
            'paid_total' => $paidTotal,
            'remaining_total' => $remaining,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function calculatePaymentStatus(int $totalHarga, int $paidTotal): string
    {
        if ($totalHarga <= 0 || $paidTotal >= $totalHarga) {
            return 'Lunas';
        }

        if ($paidTotal > 0) {
            return 'Dibayar Sebagian';
        }

        return 'Belum Dibayar';
    }

    private function purchaseExportData(string $search)
    {
        return Purchase::query()
            ->with([
                'supplier',
                'details.product:kode_barang,nama_barang,satuan',
                'payments' => fn ($query) => $query->orderBy('payment_date')->orderBy('id'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($filter) use ($search): void {
                    $filter->where('no_invoice', 'like', '%' . $search . '%')
                        ->orWhere('note', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('payment_status', 'like', '%' . $search . '%')
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search): void {
                            $supplierQuery->where('nama_supplier', 'like', '%' . $search . '%')
                                ->orWhere('alamat', 'like', '%' . $search . '%')
                                ->orWhere('nama_pic', 'like', '%' . $search . '%')
                                ->orWhere('no_hp', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('details', function ($detailQuery) use ($search): void {
                            $detailQuery->where('kode_barang', 'like', '%' . $search . '%')
                                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('nama_barang', 'like', '%' . $search . '%'));
                        });
                });
            })
            ->orderByDesc('tgl_pembelian')
            ->orderByDesc('id')
            ->get();
    }

    private function buildPurchaseExcelHtml($purchases, string $search): string
    {
        $headers = [
            'No',
            'No Invoice Pembelian',
            'Tanggal Pembelian',
            'Waktu Pencatatan',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Qty Masuk',
            'Harga Beli Satuan',
            'Total Baris',
            'Tipe Pembayaran',
            'Status Pembayaran',
            'Total Nota',
            'Sudah Dibayar',
            'Sisa Hutang',
            'Kode Supplier',
            'Nama Supplier',
            'PIC Supplier',
            'No. HP Supplier',
            'Alamat Supplier',
            'Keterangan Pembelian',
            'Metode Pembayaran Supplier',
            'Riwayat Pembayaran',
        ];

        $html = chr(239) . chr(187) . chr(191);
        $html .= '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:11pt}h2{margin-bottom:4px}p{margin:2px 0 10px}table{border-collapse:collapse}th,td{border:1px solid #555;padding:5px;vertical-align:top}th{background:#e5e7eb;font-weight:bold;text-align:center}.num{mso-number-format:"0"}.money{mso-number-format:"#,##0"}.text{mso-number-format:"\@"}</style>';
        $html .= '</head><body>';
        $html .= '<h2>Laporan Pembelian / Restock Barang</h2>';
        $html .= '<p>Waktu export: ' . $this->excelEscape(now()->format('d/m/Y H:i:s')) . '</p>';
        $html .= '<p>Filter pencarian: ' . $this->excelEscape($search !== '' ? $search : 'Semua data') . '</p>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $this->excelEscape($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $rowNumber = 1;
        foreach ($purchases as $purchase) {
            $supplier = $purchase->supplier;
            $details = $purchase->details->isNotEmpty() ? $purchase->details : collect([null]);
            $paymentMethods = $purchase->payments->pluck('payment_method')->filter()->unique()->implode(', ');
            $paymentHistory = $purchase->payments->map(function ($payment): string {
                $date = optional($payment->payment_date)->format('d/m/Y') ?: '-';
                return $date . ' | ' . ($payment->payment_method ?: '-') . ' | Rp ' . number_format((int) $payment->amount, 0, ',', '.');
            })->implode(' ; ');
            $paymentType = (int) $purchase->remaining_total <= 0
                ? 'LUNAS'
                : ((int) $purchase->paid_total > 0 ? 'SISA / CICILAN' : 'HUTANG');

            foreach ($details as $detail) {
                $product = $detail?->product;
                $cells = [
                    [$rowNumber++, 'num'],
                    [$purchase->no_invoice, 'text'],
                    [optional($purchase->tgl_pembelian)->format('d/m/Y') ?: '-', 'text'],
                    [optional($purchase->created_at)->format('d/m/Y H:i:s') ?: '-', 'text'],
                    [$detail?->kode_barang ?? '-', 'text'],
                    [$product?->nama_barang ?? ($detail?->kode_barang ?? '-'), 'text'],
                    [$product?->satuan ?? '-', 'text'],
                    [(int) ($detail?->qty ?? 0), 'num'],
                    [(int) ($detail?->harga_beli ?? 0), 'money'],
                    [(int) ($detail?->subtotal ?? 0), 'money'],
                    [$paymentType, 'text'],
                    [$purchase->payment_status ?: 'Belum Dibayar', 'text'],
                    [(int) $purchase->total_harga, 'money'],
                    [(int) $purchase->paid_total, 'money'],
                    [(int) $purchase->remaining_total, 'money'],
                    ['SUP-' . str_pad((string) ((int) ($supplier?->id ?? $purchase->supplier_id)), 4, '0', STR_PAD_LEFT), 'text'],
                    [$supplier?->nama_supplier ?? '-', 'text'],
                    [$supplier?->nama_pic ?? '-', 'text'],
                    [$supplier?->no_hp ?? '-', 'text'],
                    [$supplier?->alamat ?? '-', 'text'],
                    [$purchase->note ?: '-', 'text'],
                    [$paymentMethods !== '' ? $paymentMethods : '-', 'text'],
                    [$paymentHistory !== '' ? $paymentHistory : 'Belum ada pembayaran', 'text'],
                ];

                $html .= '<tr>';
                foreach ($cells as [$value, $class]) {
                    $html .= '<td class="' . $class . '">' . $this->excelEscape($value) . '</td>';
                }
                $html .= '</tr>';
            }
        }

        if ($rowNumber === 1) {
            $html .= '<tr><td colspan="' . count($headers) . '">Tidak ada data pembelian/restock.</td></tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    private function excelEscape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function settingsMap(): array
    {
        return Schema::hasTable('tbl_pengaturan')
            ? DB::table('tbl_pengaturan')->pluck('nilai_pengaturan', 'kunci_pengaturan')->toArray()
            : [];
    }

    private function saveSetting(string $key, ?string $value): void
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return;
        }

        DB::table('tbl_pengaturan')->updateOrInsert(
            ['kunci_pengaturan' => $key],
            ['nilai_pengaturan' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function purchasePayload(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'no_invoice' => $purchase->no_invoice,
            'tgl_pembelian' => optional($purchase->tgl_pembelian)->format('d M Y') ?: '-',
            'tgl_pembelian_value' => optional($purchase->tgl_pembelian)->format('Y-m-d') ?: now()->toDateString(),
            'supplier_id' => (int) $purchase->supplier_id,
            'status' => $purchase->status ?: '-',
            'payment_status' => $purchase->payment_status ?: 'Belum Dibayar',
            'total_item' => $purchase->total_item ?: '-',
            'total_harga' => (int) $purchase->total_harga,
            'paid_total' => (int) $purchase->paid_total,
            'remaining_total' => (int) $purchase->remaining_total,
            'note' => $purchase->note ?: '',
            'invoice_url' => route('purchase.invoice', $purchase->id),
            'payment_store_url' => route('purchase.payments.store', $purchase->id),
            'supplier' => [
                'id' => (int) ($purchase->supplier?->id ?? 0),
                'nama_supplier' => $purchase->supplier?->nama_supplier ?: '-',
                'nama_pic' => $purchase->supplier?->nama_pic ?: '-',
                'no_hp' => $purchase->supplier?->no_hp ?: '-',
                'alamat' => $purchase->supplier?->alamat ?: '-',
            ],
            'details' => $purchase->details->map(fn (PurchaseDetail $detail): array => [
                'kode_barang' => $detail->kode_barang,
                'nama_barang' => $detail->product?->nama_barang ?: $detail->kode_barang,
                'satuan' => $detail->product?->satuan ?: 'Unit',
                'qty' => (int) $detail->qty,
                'harga_beli' => (int) $detail->harga_beli,
                'subtotal' => (int) $detail->subtotal,
            ])->values(),
            'payments' => $purchase->payments->map(fn (PurchasePayment $payment): array => [
                'id' => (int) $payment->id,
                'payment_date' => optional($payment->payment_date)->format('d M Y') ?: '-',
                'payment_date_value' => optional($payment->payment_date)->format('Y-m-d') ?: now()->toDateString(),
                'amount' => (int) $payment->amount,
                'payment_method' => $payment->payment_method ?: '-',
                'payment_note' => $payment->payment_note ?: '',
            ])->values(),
        ];
    }
}
