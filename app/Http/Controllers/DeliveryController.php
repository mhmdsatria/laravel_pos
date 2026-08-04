<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $deliveries = DeliveryOrder::query()
            ->leftJoin('tbl_penjualan as p', 'p.id', '=', 'tbl_surat_jalan.penjualan_id')
            ->leftJoin('tbl_customer as c', 'c.id', '=', 'p.customer_id')
            ->select([
                'tbl_surat_jalan.*',
                'p.no_invoice',
                'p.nama_pelanggan',
                'p.customer_id',
                'c.alamat_lengkap as alamat_tujuan',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('tbl_surat_jalan.no_surat_jalan', 'like', "%{$search}%")
                        ->orWhere('p.no_invoice', 'like', "%{$search}%")
                        ->orWhere('p.nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('tbl_surat_jalan.nama_sopir', 'like', "%{$search}%")
                        ->orWhere('tbl_surat_jalan.plat_nomor', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('tbl_surat_jalan.tgl_terbit', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('tbl_surat_jalan.tgl_terbit', '<=', $dateTo))
            ->orderByDesc('tbl_surat_jalan.tgl_terbit')
            ->orderByDesc('tbl_surat_jalan.id')
            ->paginate(12)
            ->withQueryString();

        $deliveries->getCollection()->transform(function (DeliveryOrder $delivery): DeliveryOrder {
            if (blank($delivery->alamat_tujuan)) {
                $delivery->alamat_tujuan = $this->resolveCustomerAddress(
                    filled($delivery->customer_id) ? (int) $delivery->customer_id : null,
                    $delivery->nama_pelanggan
                );
            }

            return $delivery;
        });

        $invoices = $this->availableInvoices();

        return view('pages.delivery', compact(
            'deliveries',
            'invoices',
            'search',
            'dateFrom',
            'dateTo'
        ));
    }

    public function getInvoiceDetail(int $id): JsonResponse
    {
        $invoice = DB::table('tbl_penjualan as p')
            ->leftJoin('tbl_customer as c', 'c.id', '=', 'p.customer_id')
            ->where('p.id', $id)
            ->select([
                'p.id',
                'p.no_invoice',
                'p.nama_pelanggan',
                'p.tipe_pelanggan',
                'p.customer_id',
                'c.alamat_lengkap as alamat_tujuan',
            ])
            ->first();

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice penjualan tidak ditemukan.',
            ], 404);
        }

        $alamatTujuan = filled($invoice->alamat_tujuan)
            ? trim((string) $invoice->alamat_tujuan)
            : $this->resolveCustomerAddress(
                filled($invoice->customer_id) ? (int) $invoice->customer_id : null,
                $invoice->nama_pelanggan
            );

        $items = $this->remainingItemsForInvoice($id)
            ->map(fn (object $item): array => [
                'kode_barang' => $item->kode_barang,
                'nama_barang' => $item->nama_barang,
                'satuan' => $item->satuan,
                'qty_order' => (float) $item->qty_order,
                'qty_terkirim' => (float) $item->qty_terkirim,
                'sisa_kirim' => (float) $item->sisa_kirim,
                'total_belanja' => (float) $item->qty_order,
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => [
                    'id' => (int) $invoice->id,
                    'no_invoice' => $invoice->no_invoice,
                    'nama_pelanggan' => $invoice->nama_pelanggan,
                    'tipe_pelanggan' => $invoice->tipe_pelanggan,
                    'alamat_tujuan' => $alamatTujuan ?: 'Alamat lengkap pelanggan belum terdaftar di Master Pelanggan.',
                ],
                'items' => $items,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $delivery = $this->deliveryForOutput($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $delivery->id,
                'no_surat_jalan' => $delivery->no_surat_jalan,
                'no_invoice' => $delivery->no_invoice,
                'nama_pelanggan' => $delivery->nama_pelanggan,
                'alamat_tujuan' => $delivery->alamat_tujuan ?: '-',
                'tgl_terbit' => optional($delivery->tgl_terbit)->format('d M Y H:i'),
                'nama_sopir' => $delivery->nama_sopir,
                'plat_nomor' => $delivery->plat_nomor,
                'catatan' => $delivery->catatan ?: '-',
                'status' => $delivery->status,
                'penerima_lokasi' => $delivery->penerima_lokasi ?: '-',
                'items' => $delivery->details->map(fn (DeliveryOrderDetail $detail): array => [
                    'kode_barang' => $detail->kode_barang,
                    'nama_barang' => $detail->product?->nama_barang ?? $detail->kode_barang,
                    'satuan' => $detail->product?->satuan ?? 'Unit',
                    'qty_kirim' => (int) $detail->qty_kirim,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'penjualan_id' => ['required', 'integer', 'exists:tbl_penjualan,id'],
            'nama_sopir' => ['required', 'string', 'max:150'],
            'plat_nomor' => ['required', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'kode_barang' => ['required', 'array', 'min:1'],
            'kode_barang.*' => ['required', 'string', 'exists:tbl_barang,kode_barang'],
            'qty_kirim' => ['required', 'array', 'min:1'],
            'qty_kirim.*' => ['nullable', 'numeric', 'gte:0'],
        ]);

        if (count($validated['kode_barang']) !== count($validated['qty_kirim'])) {
            throw ValidationException::withMessages([
                'qty_kirim' => 'Jumlah kode barang dan kuantitas tidak konsisten.',
            ]);
        }

        $requested = [];
        foreach ($validated['kode_barang'] as $index => $code) {
            $qty = (float) ($validated['qty_kirim'][$index] ?? 0);
            if ($qty > 0) {
                $requested[$code] = ($requested[$code] ?? 0) + $qty;
            }
        }

        if ($requested === []) {
            throw ValidationException::withMessages([
                'qty_kirim' => 'Minimal satu barang harus memiliki qty kirim lebih dari nol.',
            ]);
        }

        $delivery = DB::transaction(function () use ($validated, $requested): DeliveryOrder {
            DB::table('tbl_penjualan')
                ->where('id', $validated['penjualan_id'])
                ->lockForUpdate()
                ->first();

            $detailRows = DB::table('tbl_penjualan_detail')
                ->where('penjualan_id', $validated['penjualan_id'])
                ->lockForUpdate()
                ->get(['kode_barang', 'qty']);

            if ($detailRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'penjualan_id' => 'Invoice tidak memiliki detail barang.',
                ]);
            }

            $deliveryIds = DB::table('tbl_surat_jalan')
                ->where('penjualan_id', $validated['penjualan_id'])
                ->lockForUpdate()
                ->pluck('id');

            $delivered = $deliveryIds->isEmpty()
                ? collect()
                : DB::table('tbl_surat_jalan_detail')
                    ->whereIn('surat_jalan_id', $deliveryIds)
                    ->selectRaw('kode_barang, SUM(qty_kirim) as total')
                    ->groupBy('kode_barang')
                    ->pluck('total', 'kode_barang');

            $ordered = $detailRows
                ->groupBy('kode_barang')
                ->map(fn (Collection $rows): float => (float) $rows->sum('qty'));

            foreach ($requested as $code => $qty) {
                $orderQty = (float) ($ordered[$code] ?? 0);
                $sentQty = (float) ($delivered[$code] ?? 0);
                $remaining = max(0, $orderQty - $sentQty);

                if ($orderQty <= 0) {
                    throw ValidationException::withMessages([
                        'kode_barang' => "Barang {$code} tidak ada pada invoice.",
                    ]);
                }

                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        'qty_kirim' => "Muatan {$code} melebihi sisa pesanan. Sisa hanya {$remaining}.",
                    ]);
                }
            }

            $delivery = DeliveryOrder::create([
                'penjualan_id' => $validated['penjualan_id'],
                'tgl_terbit' => now(),
                'nama_sopir' => trim($validated['nama_sopir']),
                'plat_nomor' => strtoupper(trim($validated['plat_nomor'])),
                'catatan' => filled($validated['catatan'] ?? null)
                    ? trim((string) $validated['catatan'])
                    : null,
                'status' => 'DIPROSES',
            ]);

            foreach ($requested as $code => $qty) {
                $delivery->details()->create([
                    'kode_barang' => $code,
                    'qty_kirim' => $qty,
                ]);
            }

            return $delivery;
        }, 3);

        $print = $this->attemptNativePrint($delivery);

        $payload = [
            'success' => true,
            'message' => 'Surat jalan berhasil disimpan.',
            'delivery_id' => $delivery->id,
            'no_surat_jalan' => $delivery->no_surat_jalan,
            'native_printed' => $print['printed'],
            'print_message' => $print['message'],
            'print_url' => route('delivery.print', $delivery->id),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('delivery.index')
            ->with('success', $payload['message'] . ' ' . $payload['print_message']);
    }

    public function updateStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['DIPROSES', 'DI JALAN', 'DITERIMA'])],
            'penerima_lokasi' => [
                Rule::requiredIf(fn (): bool => $request->input('status') === 'DITERIMA'),
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $delivery = DB::transaction(function () use ($id, $validated): DeliveryOrder {
            $delivery = DeliveryOrder::query()->lockForUpdate()->findOrFail($id);

            $allowed = [
                'DIPROSES' => ['DIPROSES', 'DI JALAN', 'DITERIMA'],
                'DI JALAN' => ['DI JALAN', 'DITERIMA'],
                'DITERIMA' => ['DITERIMA'],
            ];

            if (! in_array($validated['status'], $allowed[$delivery->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Perubahan {$delivery->status} ke {$validated['status']} tidak diizinkan.",
                ]);
            }

            $delivery->update([
                'status' => $validated['status'],
                'penerima_lokasi' => $validated['status'] === 'DITERIMA'
                    ? trim((string) $validated['penerima_lokasi'])
                    : null,
            ]);

            return $delivery->fresh();
        }, 3);

        $payload = [
            'success' => true,
            'message' => "Status {$delivery->no_surat_jalan} menjadi {$delivery->status}.",
        ];

        return $request->expectsJson()
            ? response()->json($payload)
            : redirect()->route('delivery.index')->with('success', $payload['message']);
    }

    public function print(int $id): View
    {
        return view('prints.delivery_order', [
            'delivery' => $this->deliveryForOutput($id),
            'copies' => 1,
            'autoPrint' => true,
        ]);
    }

    private function availableInvoices(): Collection
    {
        $ordered = DB::table('tbl_penjualan_detail')
            ->selectRaw('penjualan_id, kode_barang, SUM(qty) as qty_order')
            ->groupBy('penjualan_id', 'kode_barang');

        $sent = DB::table('tbl_surat_jalan as sj')
            ->join('tbl_surat_jalan_detail as sd', 'sd.surat_jalan_id', '=', 'sj.id')
            ->selectRaw('sj.penjualan_id, sd.kode_barang, SUM(sd.qty_kirim) as qty_sent')
            ->groupBy('sj.penjualan_id', 'sd.kode_barang');

        return DB::table('tbl_penjualan as p')
            ->joinSub($ordered, 'o', 'o.penjualan_id', '=', 'p.id')
            ->leftJoinSub($sent, 's', function ($join): void {
                $join->on('s.penjualan_id', '=', 'p.id')
                    ->on('s.kode_barang', '=', 'o.kode_barang');
            })
            ->select(['p.id', 'p.no_invoice', 'p.nama_pelanggan', 'p.tgl_transaksi'])
            ->selectRaw('SUM(o.qty_order - COALESCE(s.qty_sent, 0)) as total_sisa')
            ->groupBy('p.id', 'p.no_invoice', 'p.nama_pelanggan', 'p.tgl_transaksi')
            ->havingRaw('SUM(o.qty_order - COALESCE(s.qty_sent, 0)) > 0')
            ->orderByDesc('p.tgl_transaksi')
            ->limit(250)
            ->get();
    }

    private function remainingItemsForInvoice(int $invoiceId): Collection
    {
        $items = DB::table('tbl_penjualan_detail as d')
            ->leftJoin('tbl_barang as b', 'b.kode_barang', '=', 'd.kode_barang')
            ->where('d.penjualan_id', $invoiceId)
            ->select([
                'd.kode_barang',
                DB::raw('COALESCE(b.nama_barang, d.kode_barang) as nama_barang'),
                DB::raw("COALESCE(b.satuan, 'Unit') as satuan"),
            ])
            ->selectRaw('SUM(d.qty) as qty_order')
            ->groupBy('d.kode_barang', 'b.nama_barang', 'b.satuan')
            ->get();

        $sent = DB::table('tbl_surat_jalan as sj')
            ->join('tbl_surat_jalan_detail as sd', 'sd.surat_jalan_id', '=', 'sj.id')
            ->where('sj.penjualan_id', $invoiceId)
            ->selectRaw('sd.kode_barang, SUM(sd.qty_kirim) as qty_sent')
            ->groupBy('sd.kode_barang')
            ->pluck('qty_sent', 'kode_barang');

        return $items->map(function (object $item) use ($sent): object {
            $item->qty_order = (float) $item->qty_order;
            $item->qty_terkirim = (float) ($sent[$item->kode_barang] ?? 0);
            $item->sisa_kirim = max(0, $item->qty_order - $item->qty_terkirim);
            return $item;
        });
    }

    private function deliveryForOutput(int $id): DeliveryOrder
    {
        $delivery = DeliveryOrder::query()
            ->with(['details.product:kode_barang,nama_barang,satuan'])
            ->leftJoin('tbl_penjualan as p', 'p.id', '=', 'tbl_surat_jalan.penjualan_id')
            ->leftJoin('tbl_customer as c', 'c.id', '=', 'p.customer_id')
            ->select([
                'tbl_surat_jalan.*',
                'p.no_invoice',
                'p.nama_pelanggan',
                'p.customer_id',
                'c.alamat_lengkap as alamat_tujuan',
            ])
            ->findOrFail($id);

        if (blank($delivery->alamat_tujuan)) {
            $delivery->alamat_tujuan = $this->resolveCustomerAddress(
                filled($delivery->customer_id) ? (int) $delivery->customer_id : null,
                $delivery->nama_pelanggan
            );
        }

        return $delivery;
    }

    private function resolveCustomerAddress(?int $customerId, ?string $invoiceCustomerName): ?string
    {
        if ($customerId !== null && $customerId > 0) {
            $address = Customer::query()
                ->whereKey($customerId)
                ->value('alamat_lengkap');

            if (filled($address)) {
                return trim((string) $address);
            }
        }

        $invoiceCustomerName = trim((string) $invoiceCustomerName);

        if ($invoiceCustomerName === '') {
            return null;
        }

        $customer = Customer::query()
            ->where(function ($query) use ($invoiceCustomerName): void {
                $query->where('nama_pelanggan', $invoiceCustomerName)
                    ->orWhereRaw(
                        "? LIKE CONCAT(nama_pelanggan, ' / %')",
                        [$invoiceCustomerName]
                    );
            })
            ->orderByRaw(
                'CASE WHEN nama_pelanggan = ? THEN 0 ELSE 1 END',
                [$invoiceCustomerName]
            )
            ->orderBy('id')
            ->first(['alamat_lengkap']);

        return filled($customer?->alamat_lengkap)
            ? trim((string) $customer->alamat_lengkap)
            : null;
    }

    private function attemptNativePrint(DeliveryOrder $delivery): array
    {
        try {
            $delivery = $this->deliveryForOutput($delivery->id);

            $printerName = null;
            if (
                Schema::hasTable('tbl_pengaturan')
                && Schema::hasColumn('tbl_pengaturan', 'kunci_pengaturan')
                && Schema::hasColumn('tbl_pengaturan', 'nilai_pengaturan')
            ) {
                $printerName = DB::table('tbl_pengaturan')
                    ->where('kunci_pengaturan', 'printer_kantor')
                    ->value('nilai_pengaturan');
            }

            // Jalur utama untuk Epson LQ di Windows: satu job RAW berisi
            // tiga rangkap, dipisahkan beberapa baris tanpa form-feed.
            if (filled($printerName)) {
                $text = app(\App\Services\EpsonEscpService::class)
                    ->generateDeliveryOrder($delivery, 3);

                if (app(\App\Services\WindowsRawPrinterService::class)->print(
                    (string) $printerName,
                    $text,
                    'Surat Jalan ' . $delivery->no_surat_jalan
                )) {
                    return [
                        'printed' => true,
                        'message' => 'Tiga rangkap dicetak dalam mode RAW tanpa halaman kosong panjang.',
                    ];
                }
            }

            $facade = class_exists(\Native\Desktop\Facades\System::class)
                ? \Native\Desktop\Facades\System::class
                : (class_exists(\Native\Laravel\Facades\System::class)
                    ? \Native\Laravel\Facades\System::class
                    : null);

            if ($facade === null) {
                return [
                    'printed' => false,
                    'message' => 'Cetak RAW tidak tersedia dan NativePHP tidak aktif. Pratinjau browser digunakan.',
                ];
            }

            $html = view('prints.delivery_order', [
                'delivery' => $delivery,
                'copies' => 1,
                'autoPrint' => false,
                'nativeDirectPrint' => true,
            ])->render();

            $printer = null;
            if (filled($printerName)) {
                foreach ($facade::printers() as $candidate) {
                    $name = $candidate->displayName ?? $candidate->name ?? null;
                    if (is_string($name) && strcasecmp($name, (string) $printerName) === 0) {
                        $printer = $candidate;
                        break;
                    }
                }
            }

            // Fallback memakai ukuran standar yang paling kompatibel dengan
            // driver Windows. Custom pageSize sebelumnya memicu kertas kosong.
            $facade::print($html, $printer, [
                'pageSize' => 'A4',
                'landscape' => false,
                'copies' => 3,
                'color' => false,
                'silent' => true,
                'printBackground' => true,
            ]);

            return [
                'printed' => true,
                'message' => 'Tiga rangkap dicetak melalui fallback HTML A4.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'printed' => false,
                'message' => 'Cetak RAW/NativePHP gagal. Pratinjau browser digunakan.',
            ];
        }
    }

}

