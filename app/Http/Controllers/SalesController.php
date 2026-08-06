<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales as SalesPerson;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\SalesRefund;
use App\Models\SalesRefundDetail;
use App\Services\DesktopBridge;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Services\EpsonEscpService;
use App\Services\WindowsRawPrinterService;

class SalesController extends Controller
{

    /**
     * Menampilkan detail transaksi yang dapat diedit.
     */
    public function show(int $id): View
    {
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan,sisa_stok',
                'customer:id,nama_pelanggan,alamat_lengkap',
                'sales:id,kode_sales,nama_sales',
                'refunds.details.product:kode_barang,nama_barang,satuan',
            ])
            ->findOrFail($id);

        $customers = Schema::hasTable('tbl_customer')
            ? Customer::query()->orderBy('nama_pelanggan')->get(['id', 'nama_pelanggan', 'alamat_lengkap'])
            : collect();

        $salesPeople = Schema::hasTable('tbl_sales')
            ? SalesPerson::query()->where('status', 'Aktif')->orderBy('nama_sales')->get(['id', 'kode_sales', 'nama_sales'])
            : collect();

        return view('pages.sales-detail', compact('sale', 'customers', 'salesPeople'));
    }

    /**
     * Mencetak satu dokumen gabungan Faktur Penjualan dan Surat Jalan.
     */
    public function invoice(Request $request, int $id): View
    {
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan',
                'customer:id,nama_pelanggan,alamat_lengkap',
                'sales:id,kode_sales,nama_sales',
                'refunds.details.product:kode_barang,nama_barang,satuan',
            ])
            ->findOrFail($id);

        $autoPrint = $request->boolean('auto_print');
        $type = $request->query('type');

        return view('pages.sales-invoice', compact('sale', 'autoPrint', 'type'));
    }

public function printInvoiceEscp(
    int $id,
    EpsonEscpService $escp,
    WindowsRawPrinterService $rawPrinter,
    DesktopBridge $desktop
) {
    try {
        // Tambahkan relasi customer dan sales agar data di template bisa dipanggil
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan',
                'customer',
                'sales'
            ])
            ->findOrFail($id);

        $type = request()->query('type');
        $invoiceData = [
            'no_invoice' => $sale->no_invoice,
            'tgl_transaksi' => $sale->tgl_transaksi,
            'nama_pelanggan' => $sale->nama_pelanggan,
            'cashier_name' => $sale->cashier_name,
            'total_belanja' => $sale->total_belanja,
            'refund_total' => $sale->refund_total ?? 0,
            'alamat_pelanggan' => $sale->alamat_pelanggan ?: $sale->customer?->alamat_lengkap ?: '-',
            'nama_sales' => $sale->sales?->nama_sales ?: '-',
            'metode_bayar' => $sale->metode_bayar ?: '-',
            'status' => $sale->status ?: '-',
        ];

        if ($type === 'khusus') {
            $raw = $escp->generateInvoiceKhusus($invoiceData, $sale->details);
        } else {
            $raw = $escp->generateInvoice($invoiceData, $sale->details);
        }

        // Jika request meminta preview atau test layar
        if (request()->query('preview') || request()->query('tes')) {
            $placeholders = [
                "\x1B\x45\x1B\x47\x1B\x57\x01\x1B\x77\x01" => '[[BIG_ON]]',
                "\x1B\x48\x1B\x57\x00\x1B\x77\x00" => '[[BIG_OFF]]',
                "\x0F" => '[[CONDENSED_ON]]',
                "\x12" => '[[CONDENSED_OFF]]',
                "\x1B\x45" => '',
                "\x1B\x30" => '',
                "\x1B\x32" => '',
            ];

            $processed = str_replace(array_keys($placeholders), array_values($placeholders), $raw);
            $escaped = htmlspecialchars($processed, ENT_QUOTES, 'UTF-8');

            $htmlPreview = str_replace(
                [
                    '[[BIG_ON]]',
                    '[[BIG_OFF]]',
                    '[[CONDENSED_ON]]',
                    '[[CONDENSED_OFF]]'
                ],
                [
                    '<span style="font-size: 22px; font-weight: bold; color: #fbc02d; letter-spacing: -0.4px; display: inline-block;">',
                    '</span>',
                    '<span style="font-size: 11px; letter-spacing: -0.2px; opacity: 0.85;">',
                    '</span>'
                ],
                $escaped
            );

            return response()->make('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preview Struk ESC/P - ' . htmlspecialchars($sale->no_invoice, ENT_QUOTES, 'UTF-8') . '</title>
                <style>
                    body {
                        background: #111111;
                        color: #e0e0e0;
                        padding: 40px 20px;
                        font-family: Consolas, "Courier New", monospace;
                        display: flex;
                        justify-content: center;
                        align-items: flex-start;
                        margin: 0;
                    }
                    .preview-container {
                        background: #1a1a1a;
                        border: 1px solid #333333;
                        border-radius: 8px;
                        padding: 24px;
                        box-shadow: 0 8px 24px rgba(0,0,0,0.6);
                        width: 1100px;
                    }
                    .header {
                        border-bottom: 1px solid #333333;
                        padding-bottom: 10px;
                        margin-bottom: 15px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        font-size: 12px;
                        color: #00e5ff;
                        font-weight: bold;
                        text-transform: uppercase;
                    }
                    .badge {
                        background: #00b0ff;
                        color: #111111;
                        padding: 2px 6px;
                        border-radius: 4px;
                        font-size: 10px;
                    }
                    pre {
                        margin: 0;
                        white-space: pre-wrap;
                        line-height: 0.90;
                        font-size: 13px;
                    }
                    .footer-action {
                        margin-top: 20px;
                        border-top: 1px solid #333333;
                        padding-top: 15px;
                        text-align: right;
                    }
                    .btn-back {
                        display: inline-block;
                        padding: 8px 16px;
                        background: #00b0ff;
                        color: #111111;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 12px;
                        font-weight: bold;
                        transition: opacity 0.2s;
                    }
                    .btn-back:hover {
                        opacity: 0.9;
                    }
                </style>
            </head>
            <body>
                <div class="preview-container">
                    <div class="header">
                        <span>ESC/P Dot-Matrix Preview (79 Columns)</span>
                        <span class="badge">Epson LQ Simulator</span>
                    </div>
                    <pre>' . $htmlPreview . '</pre>
                    <div class="footer-action">
                        <a href="javascript:window.close();" class="btn-back">Tutup Preview</a>
                    </div>
                </div>
            </body>
            </html>
            ');
        }

        $printerName = $this->settingValue('printer_kantor');

        // Jalur utama Epson LQ: kirim teks RAW langsung ke Windows spooler.
        // Tidak ada form-feed sehingga kertas berhenti setelah baris terakhir.
        if ($rawPrinter->print($printerName, $raw, 'Faktur ' . $sale->no_invoice)) {
            return back()->with('success', 'Faktur berhasil dicetak ke Epson dalam mode RAW tanpa kertas kosong panjang.');
        }

        // Fallback kompatibel: Render view HTML yang baru saja kita buat
        $html = view('pages.sales-invoice', [
            'sale' => $sale,
            'copies' => 1,
            'autoPrint' => false,
            'nativeDirectPrint' => true, // Set true agar auto-print script di HTML tidak konflik dengan desktop bridge
            'type' => $type
        ])->render();

        $success = $desktop->printHtml(
            $html,
            $printerName,
            [
                // Gunakan A4 atau sesuaikan dengan driver jika ukuran custom dot matrix ditolak
                'pageSize' => 'A4', 
                'landscape' => false,
                'silent' => true,
                'printBackground' => true,
            ]
        );

        if (! $success) {
            return back()->withErrors([
                'print' => 'Printer tidak tersedia, offline, atau jalur RAW/NativePHP belum aktif.',
            ]);
        }

        return back()->with('success', 'Faktur dicetak melalui fallback HTML standar.');
    } catch (\Throwable $exception) {
        report($exception);

        return back()->withErrors([
            'print' => 'Terjadi error saat mencetak faktur: ' . $exception->getMessage(),
        ]);
    }
}
    /**
     * Memperbarui item transaksi, kuantitas terkirim, total, piutang, dan stok.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            // Header transaksi
            'tipe_pelanggan'         => ['nullable', 'in:USER,TOKO,SALES'],
            'nama_pelanggan_manual'  => ['nullable', 'string', 'max:255'],
            'alamat_pelanggan_manual'=> ['nullable', 'string', 'max:1000'],
            'customer_id'            => ['nullable', 'integer'],
            'sales_id'               => ['nullable', 'integer'],
            'metode_bayar'           => ['nullable', 'in:CASH,TEMPO'],
            'jatuh_tempo'            => ['nullable', 'date'],
            // Item transaksi
            'kode_barang' => ['required', 'array', 'min:1'],
            'kode_barang.*' => [
                'required',
                'string',
                'max:255',
                'distinct',
                'exists:tbl_barang,kode_barang',
            ],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'gt:0'],
            'qty_terkirim' => ['required', 'array', 'min:1'],
            'qty_terkirim.*' => ['required', 'numeric', 'gte:0'],
        ]);

        // Validasi silang: TEMPO wajib ada jatuh_tempo
        if (isset($validated['metode_bayar']) && $validated['metode_bayar'] === 'TEMPO' && empty($validated['jatuh_tempo'])) {
            return back()->withErrors(['jatuh_tempo' => 'Tanggal jatuh tempo wajib diisi untuk metode TEMPO.'])->withInput();
        }

        // Resolve header fields — hanya sentuh tbl_penjualan, bukan master
        $headerUpdate = [];
        $newTipePelanggan = $validated['tipe_pelanggan'] ?? null;
        $newMetodeBayar   = $validated['metode_bayar'] ?? null;

        if ($newTipePelanggan !== null) {
            $newCustomerId = null;
            $newSalesId    = null;
            $newNama       = null;
            $newAlamat     = null;

            if ($newTipePelanggan === 'USER') {
                $newNama   = trim((string) ($validated['nama_pelanggan_manual'] ?? '')) ?: 'User Umum';
                $newAlamat = trim((string) ($validated['alamat_pelanggan_manual'] ?? '')) ?: null;
            }

            if ($newTipePelanggan === 'TOKO') {
                $customerId = (int) ($validated['customer_id'] ?? 0);
                if ($customerId <= 0) {
                    return back()->withErrors(['customer_id' => 'Pilih mitra toko terlebih dahulu.'])->withInput();
                }
                $customer = Customer::query()->find($customerId);
                if (! $customer) {
                    return back()->withErrors(['customer_id' => 'Data toko tidak ditemukan.'])->withInput();
                }
                $newCustomerId = $customerId;
                $newNama       = (string) $customer->nama_pelanggan;
                $newAlamat     = $customer->alamat_lengkap;
            }

            if ($newTipePelanggan === 'SALES') {
                $customerId = (int) ($validated['customer_id'] ?? 0);
                $salesId    = (int) ($validated['sales_id'] ?? 0);
                if ($customerId <= 0) {
                    return back()->withErrors(['customer_id' => 'Pilih toko tujuan transaksi sales.'])->withInput();
                }
                if ($salesId <= 0) {
                    return back()->withErrors(['sales_id' => 'Pilih salesperson terlebih dahulu.'])->withInput();
                }
                $customer   = Customer::query()->find($customerId);
                $salesPerson = SalesPerson::query()->find($salesId);
                if (! $customer || ! $salesPerson) {
                    return back()->withErrors(['sales_id' => 'Data toko atau sales tidak ditemukan.'])->withInput();
                }
                $newCustomerId = $customerId;
                $newSalesId    = $salesId;
                $newNama       = (string) $customer->nama_pelanggan;
                $newAlamat     = $customer->alamat_lengkap;
            }

            $headerUpdate['tipe_pelanggan']  = $newTipePelanggan;
            $headerUpdate['customer_id']     = $newCustomerId;
            $headerUpdate['sales_id']        = $newSalesId;
            $headerUpdate['nama_pelanggan']  = $newNama;
            $headerUpdate['alamat_pelanggan']= $newAlamat;
        }

        if ($newMetodeBayar !== null) {
            $headerUpdate['metode_bayar'] = $newMetodeBayar;
            $headerUpdate['jatuh_tempo']  = $newMetodeBayar === 'TEMPO' ? ($validated['jatuh_tempo'] ?? null) : null;
        }

        $itemCount = count($validated['kode_barang']);

        if (
            $itemCount !== count($validated['qty'])
            || $itemCount !== count($validated['qty_terkirim'])
        ) {
            throw ValidationException::withMessages([
                'items' => 'Jumlah kode barang, qty pesanan, dan qty terkirim tidak konsisten.',
            ]);
        }

        DB::transaction(function () use ($id, $validated, $headerUpdate): void {
            $order = SalesOrder::query()
                ->lockForUpdate()
                ->findOrFail($id);

            // Lock dibuka agar item transaksi dapat di-edit/dihapus walau nota sudah dicetak/direfund
            // if ($this->saleHasRefund($order)) { ... }

            $existingDetails = SalesOrderDetail::query()
                ->where('penjualan_id', $order->id)
                ->lockForUpdate()
                ->get();

            $requestedItems = [];

            foreach ($validated['kode_barang'] as $index => $code) {
                $code = trim((string) $code);
                $qty = (float) ($validated['qty'][$index] ?? 0);
                $qtyTerkirim = (float) ($validated['qty_terkirim'][$index] ?? 0);

                if ($code === '' || $qty <= 0) {
                    continue;
                }

                if ($qtyTerkirim > $qty) {
                    throw ValidationException::withMessages([
                        'qty_terkirim' => "Qty terkirim {$code} tidak boleh melebihi qty pesanan.",
                    ]);
                }

                $requestedItems[$code] = [
                    'qty' => $qty,
                    'qty_terkirim' => $qtyTerkirim,
                ];
            }

            if ($requestedItems === []) {
                throw ValidationException::withMessages([
                    'items' => 'Transaksi wajib memiliki minimal satu barang.',
                ]);
            }

            $existingByCode = $existingDetails->groupBy('kode_barang');

            $allCodes = collect(array_keys($requestedItems))
                ->merge($existingDetails->pluck('kode_barang'))
                ->filter()
                ->unique()
                ->values();

            $products = Product::query()
                ->whereIn('kode_barang', $allCodes)
                ->lockForUpdate()
                ->get()
                ->keyBy('kode_barang');

            foreach ($allCodes as $code) {
                if (! $products->has($code)) {
                    throw ValidationException::withMessages([
                        'kode_barang' => "Barang {$code} tidak ditemukan.",
                    ]);
                }
            }

            $newTotal = 0;

            foreach ($allCodes as $code) {
                /** @var Product $product */
                $product = $products->get($code);
                $oldRows = $existingByCode->get($code, collect());

                $oldQty       = (float) $oldRows->sum('qty');
                $oldDelivered = (float) $oldRows->sum('qty_terkirim');
                $newQty       = (float) ($requestedItems[$code]['qty'] ?? 0);
                $rawDelivered = $requestedItems[$code]['qty_terkirim'] ?? null;

                // Pertahankan qty_terkirim yang sudah ada jika input bernilai 0/empty tetapi sebelumnya sudah terkirim
                if ($rawDelivered === null || ((float) $rawDelivered <= 0 && $oldDelivered > 0)) {
                    $newDelivered = $oldDelivered;
                } else {
                    $newDelivered = (float) $rawDelivered;
                }

                $newDelivered = min($newQty, max(0, $newDelivered));

                if ($newQty > 0 && $newDelivered > $newQty) {
                    throw ValidationException::withMessages([
                        'qty_terkirim' => "Qty terkirim {$code} tidak boleh melebihi qty pesanan.",
                    ]);
                }

                $difference = $newQty - $oldQty;

                // Stok sudah dipotong berdasarkan qty pesanan.
                // Perubahan qty hanya menyesuaikan selisih terhadap stok.
                if ($difference > 0) {
                    if ((float) $product->sisa_stok < $difference) {
                        throw ValidationException::withMessages([
                            'qty' => "Stok {$product->nama_barang} tidak mencukupi. Tambahan {$difference}, stok tersedia {$product->sisa_stok}.",
                        ]);
                    }

                    $product->sisa_stok = (float) $product->sisa_stok - $difference;
                    $product->save();
                } elseif ($difference < 0) {
                    $product->sisa_stok = (float) $product->sisa_stok + abs($difference);
                    $product->save();
                }

                if ($newQty <= 0) {
                    foreach ($oldRows as $oldRow) {
                        $oldRow->delete();
                    }

                    continue;
                }

                $mainDetail = $oldRows->first();

                // Harga item lama dipertahankan. Barang baru memakai harga aktif.
                $hargaJual = $mainDetail
                    ? (int) $mainDetail->harga_jual
                    : (int) $this->resolveSellingPrice(
                        $code,
                        (string) $order->tipe_pelanggan,
                        match ($order->tipe_pelanggan) {
                            'TOKO' => (int) $order->customer_id,
                            'SALES' => (int) $order->sales_id,
                            default => 0,
                        }
                    )['harga_jual'];

                if ($hargaJual <= 0) {
                    throw ValidationException::withMessages([
                        'harga_jual' => "Harga jual {$product->nama_barang} belum diset.",
                    ]);
                }

                $subtotal = (int) round((float) $newQty * (float) $hargaJual);
                $newTotal += $subtotal;

                if ($mainDetail) {
                    $mainDetail->update([
                        'qty' => $newQty,
                        'qty_terkirim' => $newDelivered,
                        'harga_jual' => $hargaJual,
                        'subtotal' => $subtotal,
                    ]);

                    $oldRows
                        ->filter(
                            fn (SalesOrderDetail $detail): bool =>
                                $detail->id !== $mainDetail->id
                        )
                        ->each
                        ->delete();
                } else {
                    SalesOrderDetail::query()->create([
                        'penjualan_id' => $order->id,
                        'kode_barang' => $code,
                        'qty' => $newQty,
                        'qty_terkirim' => $newDelivered,
                        'harga_jual' => $hargaJual,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            $updateData = [
                'total_belanja' => $newTotal,
            ];

            // Merge header fields yang sudah divalidasi sebelumnya
            $updateData = array_merge($updateData, $headerUpdate);

            // Tentukan metode bayar efektif setelah merge (bisa berubah dari request)
            $effectiveMetode = $updateData['metode_bayar'] ?? $order->metode_bayar;

            if (Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
                // Hitung total cicilan yang sudah pernah dibayar.
                // Jika sebelumnya transaksi adalah CASH dan diubah ke TEMPO, cicilan awal = 0 (hutang utuh sebesar newTotal).
                $totalSudahDibayar = 0;

                if ($order->metode_bayar === 'TEMPO') {
                    if (Schema::hasTable('tbl_receivable_payments')) {
                        $totalSudahDibayar = (int) DB::table('tbl_receivable_payments')
                            ->where('penjualan_id', $order->id)
                            ->sum('nominal');
                    } else {
                        // Fallback: hitung dari selisih total lama - sisa lama
                        $oldTotal      = (int) $order->total_belanja;
                        $oldReceivable = (int) $order->sisa_piutang;
                        $totalSudahDibayar = max(0, $oldTotal - $oldReceivable);
                    }
                }

                if ($effectiveMetode === 'TEMPO') {
                    $newReceivable = max(0, $newTotal - $totalSudahDibayar);
                    $updateData['sisa_piutang'] = $newReceivable;
                    $updateData['status']       = $newReceivable > 0 ? 'Tempo' : 'Lunas';
                } else {
                    // CASH: lunaskan piutang dan hapus histori cicilannya
                    $updateData['sisa_piutang'] = 0;
                    $updateData['status']       = 'Lunas';

                    if (Schema::hasTable('tbl_receivable_payments')) {
                        DB::table('tbl_receivable_payments')
                            ->where('penjualan_id', $order->id)
                            ->delete();
                    }
                }
            } else {
                // Kolom sisa_piutang tidak ada — update status saja
                $updateData['status'] = $effectiveMetode === 'TEMPO' ? 'Tempo' : 'Lunas';
            }

            $order->update($updateData);

            if (Schema::hasTable('stok_barang')) {
                foreach ($allCodes as $code) {
                    $product = Product::query()
                        ->where('kode_barang', $code)
                        ->first();

                    if (! $product) {
                        continue;
                    }

                    DB::table('stok_barang')->updateOrInsert(
                        ['kode_barang' => $code],
                        [
                            'nama_barang' => $product->nama_barang,
                            'stok_aktif' => (float) $product->sisa_stok,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }, 3);

        return redirect()
            ->route('sales.show', $id)
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function index(Request $request): View
{
    $search = trim((string) $request->query('search', ''));
    $today = now()->toDateString();

    $dateFrom = $request->filled('date_from')
        ? Carbon::parse((string) $request->query('date_from'))->toDateString()
        : $today;

    $dateTo = $request->filled('date_to')
        ? Carbon::parse((string) $request->query('date_to'))->toDateString()
        : $today;

    // Jika tanggal terbalik, sistem menukarnya secara otomatis.
    if ($dateFrom > $dateTo) {
        [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    }

    $query = SalesOrder::query()
        ->whereDate('tgl_transaksi', '>=', $dateFrom)
        ->whereDate('tgl_transaksi', '<=', $dateTo)
        ->orderByDesc('tgl_transaksi')
        ->orderByDesc('id');

    if ($search !== '') {
        $query->where(function ($subQuery) use ($search): void {
            $subQuery
                ->where('no_invoice', 'like', '%' . $search . '%')
                ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                ->orWhere('cashier_name', 'like', '%' . $search . '%');
        });
    }

    $salesHistory = $query
        ->paginate(10)
        ->withQueryString();

    $customers = Schema::hasTable('tbl_customer')
        ? Customer::query()
            ->orderBy('nama_pelanggan')
            ->get()
        : collect();

    $salesPeople = Schema::hasTable('tbl_sales')
        ? SalesPerson::query()
            ->where('status', 'Aktif')
            ->orderBy('nama_sales')
            ->get()
        : collect();

    return view('pages.sales', compact(
        'salesHistory',
        'customers',
        'salesPeople',
        'search',
        'dateFrom',
        'dateTo'
    ));
}

    public function print(Request $request, int $id): View|RedirectResponse
    {
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan',
                'customer:id,nama_pelanggan,alamat_lengkap',
                'sales:id,kode_sales,nama_sales',
                'refunds.details.product:kode_barang,nama_barang,satuan',
            ])
            ->find($id);

        if (! $sale) {
            return redirect()
                ->route('sales.index')
                ->withErrors(['print' => 'Data transaksi tidak ditemukan.']);
        }

        $details = $sale->details->map(function (SalesOrderDetail $detail): SalesOrderDetail {
            $detail->setAttribute(
                'nama_barang',
                $detail->product?->nama_barang ?? 'Barang (' . $detail->kode_barang . ')'
            );

            return $detail;
        });

        $autoPrint = $request->boolean('auto_print');

        return view('pages.sales-print', compact('sale', 'details', 'autoPrint'));
    }
    public function printReceiptDirect(
        int $id,
        DesktopBridge $desktop
    ): RedirectResponse {
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan',
                'customer:id,nama_pelanggan,alamat_lengkap',
                'sales:id,kode_sales,nama_sales',
                'refunds.details.product:kode_barang,nama_barang,satuan',
            ])
            ->findOrFail($id);

        $printer = $this->settingValue('printer_kasir');
        $details = $sale->details->map(function (SalesOrderDetail $detail): SalesOrderDetail {
            $detail->setAttribute(
                'nama_barang',
                $detail->product?->nama_barang ?? 'Barang (' . $detail->kode_barang . ')'
            );

            return $detail;
        });

        $html = view('pages.sales-print', [
            'sale' => $sale,
            'details' => $details,
            'nativeDirectPrint' => true,
        ])->render();

        if (! $desktop->printHtml(
            $html,
            $printer,
            ['pageSize' => $this->receiptPrintPageSize($sale), 'landscape' => false, 'silent' => true, 'printBackground' => true]
        )) {
            return redirect()
                ->route('sales.print', $id)
                ->withErrors(['printer' => 'NativePHP belum aktif. Pratinjau dibuka pada halaman yang sama.']);
        }

        return back()->with('success', 'Struk berhasil dikirim ke printer kasir.');
    }

    public function printInvoiceDirect(
        int $id,
        DesktopBridge $desktop,
        EpsonEscpService $escp,
        WindowsRawPrinterService $rawPrinter
    ): RedirectResponse {
        $sale = SalesOrder::query()
            ->with([
                'details.product:kode_barang,nama_barang,satuan',
                'customer:id,nama_pelanggan,alamat_lengkap',
                'sales:id,kode_sales,nama_sales',
                'refunds.details.product:kode_barang,nama_barang,satuan',
            ])
            ->findOrFail($id);

        $printer = $this->settingValue('printer_kantor');
        $type = request()->query('type');
        $invoiceData = [
            'no_invoice' => $sale->no_invoice,
            'tgl_transaksi' => $sale->tgl_transaksi,
            'nama_pelanggan' => $sale->nama_pelanggan,
            'cashier_name' => $sale->cashier_name,
            'total_belanja' => $sale->total_belanja,
            'refund_total' => $sale->refund_total ?? 0,
            'alamat_pelanggan' => $sale->alamat_pelanggan ?: $sale->customer?->alamat_lengkap ?: '-',
            'nama_sales' => $sale->sales?->nama_sales ?: '-',
            'metode_bayar' => $sale->metode_bayar ?: '-',
            'status' => $sale->status ?: '-',
        ];

        if ($type === 'khusus') {
            $raw = $escp->generateInvoiceKhusus($invoiceData, $sale->details);
        } else {
            $raw = $escp->generateInvoice($invoiceData, $sale->details);
        }

        if ($rawPrinter->print($printer, $raw, 'Faktur ' . $sale->no_invoice)) {
            return back()->with('success', 'Faktur / surat jalan berhasil dikirim dalam mode RAW.');
        }

        $html = view('pages.sales-invoice', [
            'sale' => $sale,
            'nativeDirectPrint' => true,
            'type' => $type,
        ])->render();

        if (! $desktop->printHtml(
            $html,
            $printer,
            ['pageSize' => 'A4', 'landscape' => false, 'silent' => true, 'printBackground' => true]
        )) {
            return redirect()
                ->route('sales.invoice', $id)
                ->withErrors(['printer' => 'Cetak RAW dan NativePHP gagal. Pratinjau dibuka pada halaman yang sama.']);
        }

        return back()->with('success', 'Faktur / surat jalan berhasil dicetak melalui fallback HTML.');
    }



    /**
     * Refund transaksi penjualan, mendukung full refund dan partial refund per barang.
     * Invoice awal tetap menjadi histori. Setiap proses refund dibuatkan Nota Refund sendiri.
     */
    public function refund(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'refund_qty' => ['required', 'array', 'min:1'],
            'refund_qty.*' => ['nullable', 'numeric', 'gte:0'],
            'refund_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            ! Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')
            || ! Schema::hasColumn('tbl_penjualan', 'refund_total')
            || ! Schema::hasTable('tbl_penjualan_refund')
            || ! Schema::hasTable('tbl_penjualan_refund_detail')
        ) {
            return back()->withErrors([
                'refund' => 'Struktur histori refund belum tersedia. Jalankan migration terlebih dahulu.',
            ]);
        }

        $refundId = null;
        $refundNumber = null;

        try {
            DB::transaction(function () use ($id, $validated, &$refundId, &$refundNumber): void {
                $order = SalesOrder::query()
                    ->with('details')
                    ->lockForUpdate()
                    ->findOrFail($id);

                $details = SalesOrderDetail::query()
                    ->where('penjualan_id', $order->id)
                    ->lockForUpdate()
                    ->get();

                if ($details->isEmpty()) {
                    throw ValidationException::withMessages([
                        'refund' => 'Detail barang transaksi tidak ditemukan.',
                    ]);
                }

                $refundInput = collect($validated['refund_qty'] ?? []);
                $refundItems = [];
                $refundTotalThisProcess = 0;

                foreach ($details as $detail) {
                    $detailId = (string) $detail->id;
                    $qtyOriginal = (float) $detail->qty;
                    $qtyAlreadyRefunded = (float) ($detail->qty_refund ?? 0);
                    $qtyAvailableToRefund = max(0, $qtyOriginal - $qtyAlreadyRefunded);
                    $qtyRequest = (float) $refundInput->get($detailId, 0);

                    if ($qtyRequest <= 0) {
                        continue;
                    }

                    if ($qtyRequest > $qtyAvailableToRefund) {
                        throw ValidationException::withMessages([
                            'refund_qty' => "Qty refund barang {$detail->kode_barang} maksimal {$qtyAvailableToRefund}.",
                        ]);
                    }

                    $lineRefundTotal = (int) round((float) $qtyRequest * (float) $detail->harga_jual);
                    $refundItems[] = [
                        'detail' => $detail,
                        'qty' => $qtyRequest,
                        'line_total' => $lineRefundTotal,
                    ];
                    $refundTotalThisProcess += $lineRefundTotal;
                }

                if ($refundItems === []) {
                    throw ValidationException::withMessages([
                        'refund_qty' => 'Isi minimal satu qty barang yang akan di-refund.',
                    ]);
                }

                $products = Product::query()
                    ->whereIn('kode_barang', collect($refundItems)->pluck('detail.kode_barang')->unique()->values()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('kode_barang');

                $processedBy = Auth::user()?->nama_lengkap ?: Auth::user()?->username ?: 'Kasir Sistem';
                $reason = trim((string) ($validated['refund_reason'] ?? ''));
                $reason = $reason !== '' ? $reason : 'Refund barang penjualan';

                $refund = SalesRefund::query()->create([
                    'penjualan_id' => $order->id,
                    'no_refund' => SalesRefund::generateRefundNumber(),
                    'refund_at' => now(),
                    'refund_total' => $refundTotalThisProcess,
                    'refund_reason' => $reason,
                    'refunded_by' => $processedBy,
                ]);

                $refundId = (int) $refund->id;
                $refundNumber = (string) $refund->no_refund;

                foreach ($refundItems as $item) {
                    /** @var SalesOrderDetail $detail */
                    $detail = $item['detail'];
                    $qtyReturn = (float) $item['qty'];
                    $lineRefundTotal = (int) $item['line_total'];

                    $product = $products->get($detail->kode_barang);
                    if (! $product) {
                        throw ValidationException::withMessages([
                            'refund' => "Barang {$detail->kode_barang} tidak ditemukan di master barang. Refund dibatalkan agar stok tidak salah.",
                        ]);
                    }

                    // Penting: hanya barang yang di-refund yang kembali ke stok.
                    $product->sisa_stok = (float) $product->sisa_stok + $qtyReturn;
                    $product->save();

                    $detail->qty_refund = (float) ($detail->qty_refund ?? 0) + $qtyReturn;
                    $detail->save();

                    SalesRefundDetail::query()->create([
                        'refund_id' => $refund->id,
                        'penjualan_detail_id' => $detail->id,
                        'kode_barang' => $detail->kode_barang,
                        'qty_refund' => $qtyReturn,
                        'harga_jual' => (int) $detail->harga_jual,
                        'subtotal_refund' => $lineRefundTotal,
                    ]);

                    if (Schema::hasTable('stok_barang')) {
                        DB::table('stok_barang')->updateOrInsert(
                            ['kode_barang' => $detail->kode_barang],
                            [
                                'nama_barang' => $product->nama_barang,
                                'stok_aktif' => (float) $product->sisa_stok,
                                'updated_at' => now(),
                            ]
                        );
                    }
                }

                $freshDetails = SalesOrderDetail::query()
                    ->where('penjualan_id', $order->id)
                    ->get();

                $totalQty = (float) $freshDetails->sum('qty');
                $totalRefundQty = (float) $freshDetails->sum('qty_refund');
                $isFullRefund = $totalQty > 0 && $totalRefundQty >= $totalQty;

                $oldRefundTotal = (int) ($order->refund_total ?? 0);
                $newRefundTotal = min((int) $order->total_belanja, $oldRefundTotal + $refundTotalThisProcess);
                $netTotalAfterRefund = max(0, (int) $order->total_belanja - $newRefundTotal);

                $appendReason = '[' . now()->format('Y-m-d H:i') . '] ' . $processedBy . ': ' . $reason . ' | ' . $refundNumber . ' | Total refund: Rp ' . number_format($refundTotalThisProcess, 0, ',', '.');
                $previousReason = trim((string) ($order->refund_reason ?? ''));

                $orderData = [
                    'refund_total' => $newRefundTotal,
                    'refund_at' => now(),
                    'refunded_by' => $processedBy,
                    'refund_reason' => $previousReason !== '' ? $previousReason . "\n" . $appendReason : $appendReason,
                ];

                if (Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
                    if ($isFullRefund) {
                        $orderData['sisa_piutang'] = 0;
                    } else {
                        $oldNetTotal = max(0, (int) $order->total_belanja - $oldRefundTotal);
                        $amountAlreadyPaid = max(0, $oldNetTotal - (int) ($order->sisa_piutang ?? 0));
                        $orderData['sisa_piutang'] = max(0, $netTotalAfterRefund - $amountAlreadyPaid);
                    }
                }

                if (Schema::hasColumn('tbl_penjualan', 'status')) {
                    $orderData['status'] = (($orderData['sisa_piutang'] ?? (int) ($order->sisa_piutang ?? 0)) > 0) ? 'Tempo' : 'Lunas';
                }

                $order->forceFill($orderData)->save();
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'refund' => 'Refund gagal diproses: ' . $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('sales.show', $id)
            ->with('success', 'Refund berhasil diproses. Nota refund ' . ($refundNumber ?: '-') . ' dibuat dan stok kembali hanya sesuai barang yang diretur.');
    }

    public function printRefund(Request $request, int $refund): View
    {
        $refundRecord = SalesRefund::query()
            ->with([
                'order.customer:id,nama_pelanggan,alamat_lengkap',
                'order.sales:id,kode_sales,nama_sales',
                'details.product:kode_barang,nama_barang,satuan',
                'details.orderDetail:id,penjualan_id,kode_barang,qty,harga_jual,subtotal',
            ])
            ->findOrFail($refund);

        $autoPrint = $request->boolean('auto_print');

        return view('pages.sales-refund-print', [
            'refund' => $refundRecord,
            'autoPrint' => $autoPrint,
        ]);
    }

    /**
     * Ukuran kertas NativePHP memakai mikron. Tinggi dihitung dari jumlah baris
     * agar printer continuous tidak melanjutkan feed ke area kosong.
     *
     * @return array{width:int,height:int}
     */
    private function receiptPrintPageSize(SalesOrder $sale): array
    {
        $itemLines = max(1, $sale->details->count());
        $refundLines = $sale->refunds->sum(
            fn ($refund): int => 1 + $refund->details->count()
        );
        $heightMm = min(1000, max(80, 68 + ($itemLines * 7) + ($refundLines * 5)));

        return [
            'width' => 80000,
            'height' => $heightMm * 1000,
        ];
    }

    /**
     * @return array{width:int,height:int}
     */
    private function invoicePrintPageSize(SalesOrder $sale, bool $compactText = false): array
    {
        $itemLines = max(1, $sale->details->count());
        $baseHeightMm = $compactText ? 75 : 155;
        $lineHeightMm = $compactText ? 6 : 9;
        $minimumHeightMm = $compactText ? 100 : 170;
        $heightMm = min(1200, max($minimumHeightMm, $baseHeightMm + ($itemLines * $lineHeightMm)));

        return [
            'width' => 210000,
            'height' => $heightMm * 1000,
        ];
    }

    private function saleHasRefund(SalesOrder $order): bool
    {
        return Schema::hasColumn('tbl_penjualan', 'refund_total')
            && (int) ($order->refund_total ?? 0) > 0;
    }

    private function saleIsFullyRefunded(SalesOrder $order): bool
    {
        if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')) {
            return false;
        }

        $details = $order->relationLoaded('details')
            ? $order->details
            : SalesOrderDetail::query()->where('penjualan_id', $order->id)->get();

        $totalQty = (float) $details->sum('qty');
        $totalRefund = (float) $details->sum('qty_refund');

        return $totalQty > 0 && $totalRefund >= $totalQty;
    }


    private function settingValue(string $key): ?string
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return null;
        }

        $value = DB::table('tbl_pengaturan')
            ->where('kunci_pengaturan', $key)
            ->value('nilai_pengaturan');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function searchProducts(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_barang')) {
            return response()->json(['data' => []]);
        }

        $keyword = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->select([
                'kode_barang',
                'nama_barang',
                'satuan',
                'sisa_stok',
                'harga_beli_terakhir',
                'harga_jual_normal',
            ])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery->where('nama_barang', 'like', '%' . $keyword . '%')
                        ->orWhere('kode_barang', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('nama_barang')
            ->limit(100)
            ->get()
            ->map(function (Product $product): array {
                return [
                    'kode_barang' => (string) $product->kode_barang,
                    'nama_barang' => (string) $product->nama_barang,
                    'satuan' => (string) ($product->satuan ?? ''),
                    'sisa_stok' => (float) ($product->sisa_stok ?? 0),
                    'harga_beli_terakhir' => (int) ($product->harga_beli_terakhir ?? 0),
                    'harga_jual_normal' => (int) ($product->harga_jual_normal ?? 0),
                ];
            });

        return response()->json(['data' => $products]);
    }

    public function getProductPrice(Request $request): JsonResponse
    {
        $kodeBarang = trim((string) $request->query('kode_barang', ''));
        $tipePelanggan = strtoupper(trim((string) $request->query('tipe_pelanggan', 'USER')));
        $targetId = (int) $request->query('target_id', 0);

        if ($kodeBarang === '') {
            return response()->json([
                'success' => false,
                'message' => 'Kode barang wajib dikirim.',
            ], 422);
        }

        $product = Product::query()->where('kode_barang', $kodeBarang)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan.',
            ], 404);
        }

        $pricePayload = $this->resolveSellingPrice($kodeBarang, $tipePelanggan, $targetId);

        return response()->json([
            'success' => true,
            'kode_barang' => $kodeBarang,
            'nama_barang' => (string) $product->nama_barang,
            'satuan' => (string) ($product->satuan ?? ''),
            'sisa_stok' => (float) ($product->sisa_stok ?? 0),
            'harga_jual' => (int) $pricePayload['harga_jual'],
            'harga_jual_normal' => (int) $pricePayload['harga_jual_normal'],
            'harga_khusus' => $pricePayload['harga_khusus'],
            'sumber_harga' => $pricePayload['sumber_harga'],
            'tipe_pelanggan' => $tipePelanggan,
            'target_id' => $targetId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipe_pelanggan' => ['required', 'in:USER,TOKO,SALES'],
            'nama_pelanggan_manual' => ['nullable', 'string', 'max:255'],
            'alamat_pelanggan_manual' => ['nullable', 'string', 'max:1000'],
            'customer_id_toko' => ['nullable', 'integer'],
            'customer_id_sales' => ['nullable', 'integer'],
            'sales_id' => ['nullable', 'integer'],
            'metode_bayar' => ['required', 'in:CASH,TEMPO'],
            'jatuh_tempo' => ['nullable', 'date'],
            'kode_barang' => ['required', 'array', 'min:1'],
            'kode_barang.*' => ['required', 'string', 'max:255', 'distinct'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'gt:0'],
            'harga_jual' => ['nullable', 'array'],
            'harga_jual.*' => ['nullable'],
            'is_manual_price' => ['nullable', 'array'],
            'is_manual_price.*' => ['nullable', 'boolean'],
            'save_price_to_master' => ['nullable', 'boolean'],
        ]);

        if ($validated['metode_bayar'] === 'TEMPO' && empty($validated['jatuh_tempo'])) {
            return back()->withErrors(['jatuh_tempo' => 'Tanggal jatuh tempo wajib diisi untuk transaksi TEMPO.'])->withInput();
        }

        $tipePelanggan = $validated['tipe_pelanggan'];
        $metodeBayar = $validated['metode_bayar'];
        $jatuhTempo = $validated['jatuh_tempo'] ?? null;
        $kodeBarangList = $validated['kode_barang'];
        $qtyList = $validated['qty'];
        $hargaJualList = $validated['harga_jual'] ?? [];
        $isManualPriceList = $validated['is_manual_price'] ?? [];
        $savePriceToMaster = $request->boolean('save_price_to_master');

        $customerId = null;
        $salesId = null;
        $targetIdForPricing = 0;
        $namaPelanggan = 'User Umum';
        $alamatPelanggan = null;

        if ($tipePelanggan === 'USER') {
            $namaPelanggan = trim((string) ($validated['nama_pelanggan_manual'] ?? '')) ?: 'User Umum';
            $alamatPelanggan = trim((string) ($validated['alamat_pelanggan_manual'] ?? '')) ?: null;
        }

        if ($tipePelanggan === 'TOKO') {
            $customerId = (int) ($validated['customer_id_toko'] ?? 0);
            if ($customerId <= 0) {
                return back()->withErrors(['customer_id_toko' => 'Pilih mitra toko terlebih dahulu.'])->withInput();
            }
            $customer = Customer::query()->find($customerId);
            if (! $customer) {
                return back()->withErrors(['customer_id_toko' => 'Data toko tidak ditemukan.'])->withInput();
            }
            $namaPelanggan = (string) $customer->nama_pelanggan;
            $alamatPelanggan = $customer->alamat_lengkap;
            $targetIdForPricing = $customerId;
        }

        if ($tipePelanggan === 'SALES') {
            $customerId = (int) ($validated['customer_id_sales'] ?? 0);
            $salesId = (int) ($validated['sales_id'] ?? 0);
            if ($customerId <= 0) {
                return back()->withErrors(['customer_id_sales' => 'Pilih target toko untuk transaksi sales.'])->withInput();
            }
            if ($salesId <= 0) {
                return back()->withErrors(['sales_id' => 'Pilih salesperson terlebih dahulu.'])->withInput();
            }
            $customer = Customer::query()->find($customerId);
            $salesPerson = SalesPerson::query()->find($salesId);
            if (! $customer || ! $salesPerson) {
                return back()->withErrors(['sales_id' => 'Data toko atau sales tidak ditemukan.'])->withInput();
            }
            $namaPelanggan = (string) $customer->nama_pelanggan;
            $alamatPelanggan = $customer->alamat_lengkap;
            $targetIdForPricing = $salesId;
        }

        $hasHargaKhusus = Schema::hasTable('tbl_harga_khusus');
        $schema = [
            'penjualan_sisa_piutang' => Schema::hasColumn('tbl_penjualan', 'sisa_piutang'),
            'harga_khusus' => $hasHargaKhusus,
            'harga_khusus_created_at' => $hasHargaKhusus && Schema::hasColumn('tbl_harga_khusus', 'created_at'),
            'harga_khusus_updated_at' => $hasHargaKhusus && Schema::hasColumn('tbl_harga_khusus', 'updated_at'),
        ];

        try {
            $order = DB::transaction(function () use ($tipePelanggan, $namaPelanggan, $alamatPelanggan, $customerId, $salesId, $targetIdForPricing, $metodeBayar, $jatuhTempo, $kodeBarangList, $qtyList, $hargaJualList, $isManualPriceList, $savePriceToMaster, $schema): SalesOrder {
                $submittedCodes = collect($kodeBarangList)->map(fn ($code): string => trim((string) $code))->filter(fn (string $code): bool => $code !== '')->unique()->values();
                if ($submittedCodes->isEmpty()) {
                    throw ValidationException::withMessages(['items' => 'Keranjang belanja masih kosong.']);
                }

                $products = Product::query()->whereIn('kode_barang', $submittedCodes->all())->lockForUpdate()->get()->keyBy('kode_barang');
                $specialPrices = collect();

                if ($schema['harga_khusus'] && in_array($tipePelanggan, ['TOKO', 'SALES'], true) && $targetIdForPricing > 0) {
                    $typeHarga = $tipePelanggan === 'TOKO' ? 'Toko' : 'Sales';
                    $specialPrices = DB::table('tbl_harga_khusus')
                        ->where('type_harga', $typeHarga)
                        ->where('id_target', $targetIdForPricing)
                        ->whereIn('kode_barang', $submittedCodes->all())
                        ->get(['kode_barang', 'harga_khusus'])
                        ->keyBy('kode_barang');
                }

                $preparedItems = [];
                $totalBelanja = 0;

                foreach ($kodeBarangList as $index => $kodeBarang) {
                    $kodeBarang = trim((string) $kodeBarang);
                    $qty = (float) ($qtyList[$index] ?? 0);
                    if ($kodeBarang === '' || $qty <= 0) continue;

                    /** @var Product|null $product */
                    $product = $products->get($kodeBarang);
                    if (! $product) {
                        throw ValidationException::withMessages(['kode_barang' => "Barang {$kodeBarang} tidak ditemukan."]);
                    }
                    if ((float) $product->sisa_stok < $qty) {
                        throw ValidationException::withMessages(['qty' => "Stok {$product->nama_barang} tidak mencukupi. Stok tersedia: {$product->sisa_stok}."]);
                    }

                    $special = $specialPrices->get($kodeBarang);
                    $hargaKhusus = $special && (int) ($special->harga_khusus ?? 0) > 0 ? (int) $special->harga_khusus : 0;
                    $hargaNormal = (int) ($product->harga_jual_normal ?? 0);
                    $hargaModal = (int) ($product->harga_beli_terakhir ?? 0);
                    $hargaDefault = $hargaKhusus > 0 ? $hargaKhusus : ($hargaNormal > 0 ? $hargaNormal : 0);
                    $hargaJualManual = $this->cleanSubmittedCurrency($hargaJualList[$index] ?? null);
                    $isManualPrice = filter_var($isManualPriceList[$index] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $hargaJual = $hargaJualManual > 0 ? $hargaJualManual : $hargaDefault;

                    if ($hargaJual <= 0) {
                        throw ValidationException::withMessages(['harga_jual' => "Harga jual {$product->nama_barang} belum diset. Isi harga manual pada baris transaksi atau atur di Master Harga."]);
                    }

                    $subtotal = (int) round((float) $qty * (float) $hargaJual);
                    $totalBelanja += $subtotal;
                    $preparedItems[] = ['kode_barang' => $kodeBarang, 'qty' => $qty, 'harga_jual' => $hargaJual, 'subtotal' => $subtotal, 'is_manual_price' => $isManualPrice];
                }

                if ($preparedItems === []) {
                    throw ValidationException::withMessages(['items' => 'Keranjang belanja masih kosong.']);
                }

                $user = Auth::user();
                $cashierName = $user?->nama_lengkap ?: $user?->username ?: 'Kasir';
                $status = $metodeBayar === 'CASH' ? 'Lunas' : 'Tempo';
                $sisaPiutang = $metodeBayar === 'TEMPO' ? $totalBelanja : 0;
                $orderData = [
                    'tgl_transaksi' => now(),
                    'tipe_pelanggan' => $tipePelanggan,
                    'nama_pelanggan' => $namaPelanggan,
                    'alamat_pelanggan' => $alamatPelanggan,
                    'customer_id' => $customerId,
                    'sales_id' => $salesId,
                    'total_belanja' => $totalBelanja,
                    'metode_bayar' => $metodeBayar,
                    'status' => $status,
                    'jatuh_tempo' => $jatuhTempo,
                    'cashier_name' => $cashierName,
                ];
                if ($schema['penjualan_sisa_piutang']) $orderData['sisa_piutang'] = $sisaPiutang;

                $order = SalesOrder::query()->create($orderData);
                $now = now();
                $detailRows = array_map(fn (array $item): array => [
                    'penjualan_id' => $order->id,
                    'kode_barang' => $item['kode_barang'],
                    'qty' => $item['qty'],
                    'qty_terkirim' => $metodeBayar === 'CASH' ? $item['qty'] : 0,
                    'harga_jual' => $item['harga_jual'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $preparedItems);
                SalesOrderDetail::query()->insert($detailRows);

                if ($savePriceToMaster && $schema['harga_khusus'] && in_array($tipePelanggan, ['TOKO', 'SALES'], true) && $targetIdForPricing > 0) {
                    $typeHarga = $tipePelanggan === 'TOKO' ? 'Toko' : 'Sales';
                    foreach ($preparedItems as $item) {
                        if (! (bool) ($item['is_manual_price'] ?? false) || (int) $item['harga_jual'] <= 0) continue;
                        $match = ['type_harga' => $typeHarga, 'id_target' => $targetIdForPricing, 'kode_barang' => $item['kode_barang']];
                        $values = ['harga_khusus' => (int) $item['harga_jual']];
                        if ($schema['harga_khusus_updated_at']) $values['updated_at'] = $now;
                        if ($schema['harga_khusus_created_at'] && ! DB::table('tbl_harga_khusus')->where($match)->exists()) $values['created_at'] = $now;
                        DB::table('tbl_harga_khusus')->updateOrInsert($match, $values);
                    }
                }

                foreach ($preparedItems as $item) {
                    /** @var Product $product */
                    $product = $products->get($item['kode_barang']);
                    $product->sisa_stok = max(0, (float) $product->sisa_stok - (float) $item['qty']);
                    $product->save();
                }

                return $order;
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->withErrors(['sales_error' => 'Transaksi gagal disimpan: ' . $exception->getMessage()]);
        }

        $successMessage = 'Transaksi berhasil disimpan dan stok berhasil dipotong.';
        if ($savePriceToMaster && in_array($tipePelanggan, ['TOKO', 'SALES'], true)) {
            $successMessage .= ' Harga manual yang dipilih juga tersimpan ke Master Harga.';
        }
        return redirect()->route('sales.show', $order->id)->with('success', $successMessage);
    }



    private function cleanSubmittedCurrency(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
    }

    private function resolveSellingPrice(string $kodeBarang, string $tipePelanggan = 'USER', int $targetId = 0): array
    {
        $product = Product::query()->where('kode_barang', $kodeBarang)->first();

        if (! $product) {
            return [
                'harga_jual' => 0,
                'harga_jual_normal' => 0,
                'harga_khusus' => null,
                'sumber_harga' => 'barang_tidak_ditemukan',
            ];
        }

        $normalPrice = (int) ($product->harga_jual_normal ?? 0);
        $fallbackModal = (int) ($product->harga_beli_terakhir ?? 0);
        $hargaKhusus = null;
        $source = 'harga_jual_normal';

        if (in_array($tipePelanggan, ['TOKO', 'SALES'], true) && $targetId > 0 && Schema::hasTable('tbl_harga_khusus')) {
            $typeHarga = $tipePelanggan === 'TOKO' ? 'Toko' : 'Sales';
            $hargaKhususRaw = DB::table('tbl_harga_khusus')
                ->where('type_harga', $typeHarga)
                ->where('id_target', $targetId)
                ->where('kode_barang', $kodeBarang)
                ->value('harga_khusus');

            if ($hargaKhususRaw !== null && (int) $hargaKhususRaw > 0) {
                $hargaKhusus = (int) $hargaKhususRaw;
                return [
                    'harga_jual' => $hargaKhusus,
                    'harga_jual_normal' => $normalPrice,
                    'harga_khusus' => $hargaKhusus,
                    'sumber_harga' => 'harga_khusus_' . strtolower($typeHarga),
                ];
            }
        }

        if ($normalPrice > 0) {
            return [
                'harga_jual' => $normalPrice,
                'harga_jual_normal' => $normalPrice,
                'harga_khusus' => $hargaKhusus,
                'sumber_harga' => $source,
            ];
        }

        return [
            'harga_jual' => 0,
            'harga_jual_normal' => $normalPrice,
            'harga_khusus' => $hargaKhusus,
            'sumber_harga' => 'harga_belum_diset',
        ];
    }
}
