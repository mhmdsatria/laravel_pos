<?php

namespace App\Http\Controllers;

use App\Models\DataTagihan;
use App\Models\DataTagihanDetail;
use App\Models\ReceivablePayment;
use App\Models\Sales;
use App\Models\SalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DataTagihanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $salesFilter = $request->query('sales_id');
        $statusFilter = trim((string) $request->query('status', ''));

        $salesmen = Schema::hasTable('tbl_sales')
            ? Sales::where('status', 'Aktif')->orderBy('nama_sales')->get()
            : collect();

        if (! Schema::hasTable('tbl_data_tagihan')) {
            return view('pages.data_tagihan.index', [
                'tagihans' => new LengthAwarePaginator([], 0, 15),
                'salesmen' => $salesmen,
                'search' => $search,
                'salesFilter' => $salesFilter,
                'statusFilter' => $statusFilter,
                'totalSheets' => 0,
                'totalNominalDibawa' => 0,
                'totalNominalSelesai' => 0,
            ]);
        }

        $query = DataTagihan::query()->with(['sales', 'details']);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('no_do', 'like', '%'.$search.'%')
                    ->orWhere('salesman_name', 'like', '%'.$search.'%')
                    ->orWhereHas('details', function ($dq) use ($search): void {
                        $dq->where('no_invoice', 'like', '%'.$search.'%')
                            ->orWhere('nama_toko', 'like', '%'.$search.'%');
                    });
            });
        }

        if (! empty($salesFilter)) {
            $query->where('sales_id', $salesFilter);
        }

        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $tagihans = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $totalSheets = DataTagihan::count();
        $totalNominalDibawa = (int) DataTagihan::where('status', 'DIBAWA')->sum('total_tagihan');
        $totalNominalSelesai = (int) DataTagihan::where('status', 'SELESAI')->sum('total_bayar');

        $salesmen = Sales::where('status', 'Aktif')->orderBy('nama_sales')->get();

        return view('pages.data_tagihan.index', [
            'tagihans' => $tagihans,
            'salesmen' => $salesmen,
            'search' => $search,
            'salesFilter' => $salesFilter,
            'statusFilter' => $statusFilter,
            'totalSheets' => $totalSheets,
            'totalNominalDibawa' => $totalNominalDibawa,
            'totalNominalSelesai' => $totalNominalSelesai,
        ]);
    }

    public function create(): View
    {
        $salesmen = Sales::where('status', 'Aktif')->orderBy('nama_sales')->get();
        $nextNoDo = DataTagihan::generateNextNumber();

        return view('pages.data_tagihan.create', [
            'salesmen' => $salesmen,
            'nextNoDo' => $nextNoDo,
            'todayDate' => now()->format('Y-m-d'),
        ]);
    }

    public function getUnpaidInvoices(Request $request): JsonResponse
    {
        $salesId = $request->query('sales_id');
        $search = trim((string) $request->query('search', ''));

        $query = SalesOrder::query()
            ->with(['customer', 'sales'])
            ->whereIn('metode_bayar', ['TEMPO', 'tempo', 'Tempo'])
            ->where('sisa_piutang', '>', 0);

        if (! empty($salesId)) {
            $query->where('sales_id', $salesId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('no_invoice', 'like', '%'.$search.'%')
                    ->orWhere('nama_pelanggan', 'like', '%'.$search.'%');
            });
        }

        $invoices = $query->orderBy('jatuh_tempo')->orderBy('id')->get()->map(function ($order): array {
            return [
                'id' => $order->id,
                'no_invoice' => $order->no_invoice,
                'tgl_transaksi' => optional($order->tgl_transaksi)->format('Y-m-d'),
                'tgl_transaksi_formatted' => optional($order->tgl_transaksi)->translatedFormat('d-M-Y'),
                'jatuh_tempo' => optional($order->jatuh_tempo)->format('Y-m-d'),
                'jatuh_tempo_formatted' => optional($order->jatuh_tempo)->translatedFormat('d-M-Y') ?? '-',
                'nama_toko' => $order->nama_pelanggan,
                'sales_name' => optional($order->sales)->nama_sales ?? '-',
                'sisa_piutang' => (int) $order->sisa_piutang,
                'sisa_piutang_formatted' => 'Rp '.number_format((int) $order->sisa_piutang, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tgl_dt' => ['required', 'date'],
            'sales_id' => ['required', 'exists:tbl_sales,id'],
            'invoices' => ['required', 'array', 'min:1'],
            'invoices.*' => ['exists:tbl_penjualan,id'],
            'catatan' => ['nullable', 'string'],
        ], [
            'sales_id.required' => 'Salesman wajib dipilih.',
            'invoices.required' => 'Minimal pilih 1 invoice piutang untuk dibawain.',
            'invoices.min' => 'Minimal pilih 1 invoice piutang untuk dibawain.',
        ]);

        DB::beginTransaction();

        try {
            $sales = Sales::findOrFail($validated['sales_id']);
            $salesOrders = SalesOrder::whereIn('id', $validated['invoices'])->get();

            $totalNominal = $salesOrders->sum('sisa_piutang');

            $tagihan = DataTagihan::create([
                'no_do' => DataTagihan::generateNextNumber(),
                'tgl_dt' => $validated['tgl_dt'],
                'sales_id' => $sales->id,
                'salesman_name' => $sales->nama_sales,
                'total_tagihan' => $totalNominal,
                'total_bayar' => 0,
                'status' => 'DIBAWA',
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'Admin',
            ]);

            foreach ($salesOrders as $order) {
                DataTagihanDetail::create([
                    'data_tagihan_id' => $tagihan->id,
                    'penjualan_id' => $order->id,
                    'no_invoice' => $order->no_invoice,
                    'tgl_inv' => optional($order->tgl_transaksi)->format('Y-m-d'),
                    'tgl_jatuh_tempo' => optional($order->jatuh_tempo)->format('Y-m-d'),
                    'nama_toko' => $order->nama_pelanggan,
                    'nominal_tagihan' => (int) $order->sisa_piutang,
                    'bayar' => 0,
                    'metode_bayar' => null,
                    'status' => 'DIBAWA',
                ]);
            }

            DB::commit();

            return redirect()->route('data-tagihan.show', $tagihan->id)
                ->with('success', 'Data Tagihan berhasil dibuat dan siap dicetak.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal membuat Data Tagihan: '.$th->getMessage()]);
        }
    }

    public function show(int $id): View
    {
        $tagihan = DataTagihan::with(['sales', 'details.salesOrder'])->findOrFail($id);

        return view('pages.data_tagihan.show', [
            'tagihan' => $tagihan,
        ]);
    }

    public function print(int $id): View
    {
        $tagihan = DataTagihan::with(['sales', 'details.salesOrder'])->findOrFail($id);

        return view('pages.data_tagihan.print', [
            'tagihan' => $tagihan,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $validated = $request->validate([
            'sales_id' => ['required', 'integer', 'exists:tbl_sales,id'],
            'tgl_dari' => ['nullable', 'date'],
            'tgl_sampai' => ['nullable', 'date', 'after_or_equal:tgl_dari'],
        ], [
            'sales_id.required' => 'Pilih salesman terlebih dahulu untuk export history.',
            'tgl_sampai.after_or_equal' => 'Tanggal sampai tidak boleh sebelum tanggal mulai.',
        ]);

        $sales = Sales::findOrFail($validated['sales_id']);
        $tagihans = DataTagihan::query()
            ->with(['details' => fn ($query) => $query->orderBy('id')])
            ->where('sales_id', $sales->id)
            ->when($validated['tgl_dari'] ?? null, fn ($query, $date) => $query->whereDate('tgl_dt', '>=', $date))
            ->when($validated['tgl_sampai'] ?? null, fn ($query, $date) => $query->whereDate('tgl_dt', '<=', $date))
            ->orderBy('tgl_dt')
            ->orderBy('id')
            ->get();

        $period = match (true) {
            ! empty($validated['tgl_dari']) && ! empty($validated['tgl_sampai']) => Carbon::parse($validated['tgl_dari'])->format('d/m/Y').' - '.Carbon::parse($validated['tgl_sampai'])->format('d/m/Y'),
            ! empty($validated['tgl_dari']) => 'Mulai '.Carbon::parse($validated['tgl_dari'])->format('d/m/Y'),
            ! empty($validated['tgl_sampai']) => 'Sampai '.Carbon::parse($validated['tgl_sampai'])->format('d/m/Y'),
            default => 'Semua periode',
        };

        $safeSalesName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $sales->nama_sales) ?: 'sales';
        $filename = 'History_Tagihan_'.trim($safeSalesName, '_').'_'.now()->format('Ymd_His').'.xls';
        $html = $this->buildTagihanExcelHtml($tagihans, $sales->nama_sales, $period);

        if ($request->header('X-Excel-Transport') === 'base64') {
            return response()->json([
                'filename' => $filename,
                'content' => base64_encode($html),
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function buildTagihanExcelHtml($tagihans, string $salesName, string $period): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rows = '';
        $totalTagihan = 0;
        $totalBayar = 0;

        foreach ($tagihans as $tagihan) {
            foreach ($tagihan->details as $detail) {
                $method = match (strtoupper((string) $detail->metode_bayar)) {
                    'TRANSFER' => 'TF',
                    'CASH' => 'CASH',
                    default => '',
                };
                $tagihanAmount = (int) $detail->nominal_tagihan;
                $bayarAmount = (int) $detail->bayar;
                $totalTagihan += $tagihanAmount;
                $totalBayar += $bayarAmount;

                $rows .= '<tr>'
                    .'<td style="mso-number-format:\@">'.$escape($tagihan->no_do).'</td>'
                    .'<td>'.$escape(optional($tagihan->tgl_dt)->format('d/m/Y')).'</td>'
                    .'<td>'.$escape(optional($detail->tgl_inv)->format('d/m/Y')).'</td>'
                    .'<td>'.$escape(optional($detail->tgl_jatuh_tempo)->format('d/m/Y')).'</td>'
                    .'<td>'.$escape(strtoupper((string) $detail->nama_toko)).'</td>'
                    .'<td class="number">'.$tagihanAmount.'</td>'
                    .'<td class="number">'.$bayarAmount.'</td>'
                    .'<td>'.$method.'</td>'
                    .'</tr>';
            }
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="8" class="empty">Tidak ada data tagihan pada periode ini.</td></tr>';
        }

        return "\xEF\xBB\xBF".'<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            .'body{font-family:Arial,sans-serif;font-size:11pt}h2{margin:0 0 8px}p{margin:2px 0}'
            .'table{border-collapse:collapse;margin-top:14px}th,td{border:1px solid #333;padding:6px 8px}'
            .'th{background:#1d4ed8;color:#fff;text-align:center}.number{text-align:right;mso-number-format:"#,##0"}'
            .'.total{font-weight:bold;background:#e5e7eb}.empty{text-align:center;color:#666}'
            .'</style></head><body><h2>HISTORY DATA TAGIHAN SALES</h2>'
            .'<p><strong>Sales:</strong> '.$escape($salesName).'</p>'
            .'<p><strong>Periode:</strong> '.$escape($period).'</p>'
            .'<table><thead><tr><th>NO DT</th><th>TGL DIBAWA</th><th>TGL INV</th><th>TGL JT</th>'
            .'<th>NAMA TOKO</th><th>JUMLAH TAGIHAN</th><th>JUMLAH BAYAR</th><th>TYPE TF/CASH</th>'
            .'</tr></thead><tbody>'.$rows.'<tr class="total"><td colspan="5" style="text-align:center">TOTAL</td>'
            .'<td class="number">'.$totalTagihan.'</td><td class="number">'.$totalBayar.'</td><td></td></tr>'
            .'</tbody></table></body></html>';
    }

    public function settle(int $id): View
    {
        $tagihan = DataTagihan::with(['sales', 'details.salesOrder'])->findOrFail($id);

        // Opsi B: Setoran bisa dilakukan selama status DIBAWA (belum semua lunas)
        // Tidak perlu blokir jika status SELESAI karena validasi ada di processSettlement

        return view('pages.data_tagihan.settle', [
            'tagihan' => $tagihan,
        ]);
    }

    public function processSettlement(Request $request, int $id): RedirectResponse
    {
        $tagihan = DataTagihan::with('details')->findOrFail($id);

        // Opsi A: Lembaran langsung SELESAI setelah setoran pertama.
        // Toko yang belum/baru cicil bisa ditagih lagi lewat lembaran baru
        // (invoice dengan sisa_piutang > 0 otomatis muncul kembali di form buat lembaran).
        if ($tagihan->status === 'SELESAI') {
            return redirect()->route('data-tagihan.show', $tagihan->id)
                ->with('info', 'Lembaran tagihan ini sudah disetorkan. Buat lembaran tagihan baru untuk tagihan susulan.');
        }

        $validated = $request->validate([
            'payments' => ['required', 'array'],
            'payments.*.detail_id' => ['required', 'exists:tbl_data_tagihan_detail,id'],
            'payments.*.bayar' => ['nullable', 'numeric', 'min:0'],
            'payments.*.metode_bayar' => ['nullable', 'in:CASH,TRANSFER'],
        ]);

        DB::beginTransaction();

        try {
            $totalBayarAll = 0;

            foreach ($validated['payments'] as $paymentItem) {
                $detail = DataTagihanDetail::where('data_tagihan_id', $tagihan->id)
                    ->where('id', $paymentItem['detail_id'])
                    ->firstOrFail();

                $bayarAmount = (int) ($paymentItem['bayar'] ?? 0);
                $metodeBayar = $paymentItem['metode_bayar'] ?? null;

                if ($bayarAmount > 0) {
                    $order = SalesOrder::where('id', $detail->penjualan_id)->lockForUpdate()->first();

                    if ($order && $order->sisa_piutang > 0) {
                        // Bayar tidak boleh melebihi sisa piutang nota
                        $actualPay = min($bayarAmount, (int) $order->sisa_piutang);

                        ReceivablePayment::create([
                            'penjualan_id' => $order->id,
                            'tgl_bayar' => now()->format('Y-m-d'),
                            'nominal' => $actualPay,
                            'penerima_kasir' => Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'Sales '.$tagihan->salesman_name,
                        ]);

                        $newBalance = max(0, (int) $order->sisa_piutang - $actualPay);
                        $order->sisa_piutang = $newBalance;
                        if ($newBalance === 0) {
                            $order->status = 'Lunas';
                        }
                        $order->save();

                        $detail->bayar = $actualPay;
                        $detail->metode_bayar = $metodeBayar ?? 'CASH';
                        // LUNAS jika sisa nota = 0, CICIL jika masih ada sisa
                        $detail->status = ($newBalance === 0) ? 'LUNAS' : 'CICIL';
                        $detail->save();

                        $totalBayarAll += $actualPay;
                    }
                } else {
                    // Toko tidak bayar di setoran ini
                    $detail->bayar = 0;
                    $detail->status = 'TIDAK_BAYAR';
                    $detail->save();
                }
            }

            // Lembaran langsung SELESAI setelah setoran diterima
            $tagihan->total_bayar = $totalBayarAll;
            $tagihan->status = 'SELESAI';
            $tagihan->save();

            DB::commit();

            return redirect()->route('data-tagihan.show', $tagihan->id)
                ->with('success', 'Setoran berhasil disimpan. Untuk toko yang belum lunas, buat lembaran tagihan baru.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal memproses setoran: '.$th->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $tagihan = DataTagihan::findOrFail($id);

        if ($tagihan->status === 'SELESAI') {
            return redirect()->route('data-tagihan.index')
                ->withErrors(['error' => 'Data Tagihan yang sudah diproses setoran tidak dapat dihapus.']);
        }

        $tagihan->delete();

        return redirect()->route('data-tagihan.index')
            ->with('success', 'Data Tagihan berhasil dihapus.');
    }
}
