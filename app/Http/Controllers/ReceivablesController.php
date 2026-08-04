<?php

namespace App\Http\Controllers;

use App\Models\ReceivablePayment;
use App\Models\SalesOrder;
use App\Services\DesktopBridge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceivablesController extends Controller
{

    private function withoutRefundedSales($query, string $alias = '')
    {
        // Refund sebagian tetap boleh muncul sebagai piutang netto. Full refund otomatis sisa_piutang = 0.
        return $query;
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', 'belum_lunas'));

        if (! Schema::hasTable('tbl_penjualan')) {
            return view('pages.receivables', [
                'debts' => collect(),
                'totalOutstanding' => 0,
                'activeDebtors' => 0,
                'overdueTotal' => 0,
                'recoveryRate' => 0,
                'search' => $search,
                'statusFilter' => $statusFilter,
                'totalFilteredInitial' => 0,
                'totalFilteredOutstanding' => 0,
                'totalFilteredPaid' => 0,
            ]);
        }

        $debts = $this->debtsQuery($search, $statusFilter)
            ->orderByRaw('CASE WHEN sisa_piutang > 0 THEN 0 ELSE 1 END')
            ->orderBy('jatuh_tempo')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $filteredQuery = $this->debtsQuery($search, $statusFilter);
        $totalFilteredInitial = (int) (clone $filteredQuery)->sum('total_belanja');
        $totalFilteredOutstanding = (int) (clone $filteredQuery)->sum('sisa_piutang');
        $totalFilteredPaid = max(0, $totalFilteredInitial - $totalFilteredOutstanding);

        $baseTempo = SalesOrder::query()->where('metode_bayar', 'TEMPO');
        $netDebtExpression = Schema::hasColumn('tbl_penjualan', 'refund_total')
            ? 'CASE WHEN COALESCE(total_belanja, 0) > COALESCE(refund_total, 0) THEN COALESCE(total_belanja, 0) - COALESCE(refund_total, 0) ELSE 0 END'
            : 'COALESCE(total_belanja, 0)';

        $totalOutstanding = (int) (clone $baseTempo)
            ->where('sisa_piutang', '>', 0)
            ->sum('sisa_piutang');

        $activeDebtors = (int) (clone $baseTempo)
            ->where('sisa_piutang', '>', 0)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $overdueCutoff = Carbon::today()->subDays(30)->toDateString();
        $overdueTotal = (int) (clone $baseTempo)
            ->where('sisa_piutang', '>', 0)
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<=', $overdueCutoff)
            ->sum('sisa_piutang');

        $totalDebtInitial = (int) (clone $baseTempo)->sum(DB::raw($netDebtExpression));
        $totalPaid = max(0, $totalDebtInitial - (int) (clone $baseTempo)->sum('sisa_piutang'));
        $recoveryRate = $totalDebtInitial > 0 ? round(($totalPaid / $totalDebtInitial) * 100, 1) : 0;

        return view('pages.receivables', [
            'debts' => $debts,
            'totalOutstanding' => $totalOutstanding,
            'activeDebtors' => $activeDebtors,
            'overdueTotal' => $overdueTotal,
            'recoveryRate' => $recoveryRate,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalFilteredInitial' => $totalFilteredInitial,
            'totalFilteredOutstanding' => $totalFilteredOutstanding,
            'totalFilteredPaid' => $totalFilteredPaid,
        ]);
    }

    public function exportExcel(Request $request, DesktopBridge $desktop)
    {
        try {
            if (! Schema::hasTable('tbl_penjualan')) {
                return redirect()
                    ->route('receivables.index', $request->query())
                    ->withErrors(['export_excel' => 'Tabel penjualan belum tersedia.']);
            }

            $search = trim((string) $request->query('search', ''));
            $debts = $this->allDebts($search);
            $filename = 'Detail_Piutang_' . now()->format('Ymd_His') . '.xls';
            $html = $this->buildReceivablesExcelHtmlNoXml($debts, $search);

            return $this->saveOrDownloadReceivableHtmlExcel(
                $request,
                $desktop,
                $html,
                $filename,
                'Excel piutang berhasil disimpan',
                'Pilih Folder Simpan Excel Piutang'
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('receivables.index', $request->query())
                ->withErrors(['export_excel' => 'Gagal menyimpan Excel piutang: ' . $exception->getMessage()]);
        }
    }

    public function exportPdf(Request $request, DesktopBridge $desktop)
    {
        try {
            if (! Schema::hasTable('tbl_penjualan')) {
                return redirect()
                    ->route('receivables.index', $request->query())
                    ->withErrors(['export_pdf' => 'Tabel penjualan belum tersedia.']);
            }

            $search = trim((string) $request->query('search', ''));
            $debts = $this->allDebts($search);
            $filename = 'Detail_Piutang_' . now()->format('Ymd_His') . '.pdf';

            $pdf = Pdf::loadView('pages.receivables_pdf', [
                'debts' => $debts,
                'search' => $search,
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape');

            if ($desktop->available()) {
                $targetPath = $this->chooseReceivableDesktopExportPath(
                    $request,
                    $desktop,
                    $filename,
                    'pdf',
                    'Pilih Folder Simpan PDF Piutang'
                );

                if ($targetPath instanceof RedirectResponse) {
                    return $targetPath;
                }

                file_put_contents($targetPath, $pdf->output());
                $this->saveSetting('export_directory', dirname($targetPath));

                return redirect()
                    ->route('receivables.index', $request->query())
                    ->with('success', 'PDF piutang berhasil disimpan: ' . $targetPath);
            }

            return $pdf->download($filename);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('receivables.index', $request->query())
                ->withErrors(['export_pdf' => 'Gagal menyimpan PDF piutang: ' . $exception->getMessage()]);
        }
    }

    public function showPiutang(int $id): JsonResponse
    {
        $order = SalesOrder::query()
            ->with(['customer', 'sales', 'receivablePayments' => function ($query): void {
                $query->orderBy('tgl_bayar')->orderBy('id');
            }])
            ->findOrFail($id);

        $payments = $order->receivablePayments->map(function (ReceivablePayment $payment): array {
            return [
                'id' => $payment->id,
                'tgl_bayar' => optional($payment->tgl_bayar)->format('Y-m-d'),
                'tgl_bayar_label' => optional($payment->tgl_bayar)->translatedFormat('d M Y'),
                'nominal' => (int) $payment->nominal,
                'nominal_label' => 'Rp ' . number_format((int) $payment->nominal, 0, ',', '.'),
                'penerima_kasir' => $payment->penerima_kasir,
            ];
        })->values();

        $totalPaid = (int) $payments->sum('nominal');

        return response()->json([
            'id' => $order->id,
            'no_invoice' => $order->no_invoice,
            'nama_pelanggan' => $order->nama_pelanggan,
            'sales_name' => optional($order->sales)->nama_sales ?? '-',
            'jatuh_tempo' => optional($order->jatuh_tempo)->format('Y-m-d'),
            'jatuh_tempo_label' => optional($order->jatuh_tempo)->translatedFormat('d M Y') ?? '-',
            'total_belanja' => (int) $order->total_belanja,
            'total_belanja_label' => 'Rp ' . number_format((int) $order->total_belanja, 0, ',', '.'),
            'total_paid' => $totalPaid,
            'total_paid_label' => 'Rp ' . number_format($totalPaid, 0, ',', '.'),
            'sisa_piutang' => (int) $order->sisa_piutang,
            'sisa_piutang_label' => 'Rp ' . number_format((int) $order->sisa_piutang, 0, ',', '.'),
            'status' => $order->status,
            'payments' => $payments,
        ]);
    }

    public function storeCicilan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penjualan_id' => ['required', 'integer', 'exists:tbl_penjualan,id'],
            'tgl_bayar' => ['required', 'date'],
            'nominal' => ['required', 'integer', 'min:1'],
        ], [
            'penjualan_id.required' => 'Nota piutang belum dipilih.',
            'tgl_bayar.required' => 'Tanggal bayar wajib diisi.',
            'nominal.required' => 'Nominal cicilan wajib diisi.',
            'nominal.min' => 'Nominal cicilan harus lebih dari 0.',
        ]);

        DB::beginTransaction();

        try {
            $order = SalesOrder::query()
                ->where('id', $validated['penjualan_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->metode_bayar !== 'TEMPO') {
                throw new \RuntimeException('Nota ini bukan nota tempo.');
            }

            $currentBalance = (int) $order->sisa_piutang;
            $nominal = (int) $validated['nominal'];

            if ($currentBalance <= 0) {
                throw new \RuntimeException('Piutang nota ini sudah lunas.');
            }

            if ($nominal > $currentBalance) {
                throw new \RuntimeException('Nominal cicilan melebihi sisa piutang berjalan.');
            }

            ReceivablePayment::query()->create([
                'penjualan_id' => $order->id,
                'tgl_bayar' => $validated['tgl_bayar'],
                'nominal' => $nominal,
                'penerima_kasir' => Auth::user()->nama_lengkap ?? Auth::user()->username ?? 'Kasir Sistem',
            ]);

            $newBalance = max(0, $currentBalance - $nominal);

            $order->sisa_piutang = $newBalance;
            if ($newBalance === 0) {
                $order->status = 'Lunas';
            }
            $order->save();

            DB::commit();

            $message = $newBalance === 0
                ? 'Cicilan berhasil dicatat. Nota sudah lunas.'
                : 'Cicilan berhasil dicatat. Sisa piutang diperbarui.';

            return redirect()->route('receivables.index')->with('success', $message);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()
                ->route('receivables.index')
                ->withInput()
                ->withErrors(['receivables_error' => $exception->getMessage()]);
        }
    }

    private function debtsQuery(string $search, string $statusFilter = 'belum_lunas')
    {
        $query = SalesOrder::query()
            ->with(['customer', 'sales', 'receivablePayments'])
            ->where('metode_bayar', 'TEMPO');

        if ($statusFilter === 'belum_lunas') {
            $query->where('sisa_piutang', '>', 0);
        } elseif ($statusFilter === 'lunas') {
            $query->where('sisa_piutang', '<=', 0);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('no_invoice', 'like', '%' . $search . '%')
                    ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('jatuh_tempo', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                        $customerQuery->where('kode_cuts', 'like', '%' . $search . '%')
                            ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                            ->orWhere('alamat_lengkap', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('sales', function ($salesQuery) use ($search): void {
                        $salesQuery->where('kode_sales', 'like', '%' . $search . '%')
                            ->orWhere('nama_sales', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    private function allDebts(string $search, string $statusFilter = 'belum_lunas')
    {
        return $this->debtsQuery($search, $statusFilter)
            ->with([
                'details.product',
                'customer',
                'sales',
                'receivablePayments' => function ($query): void {
                    $query->orderBy('tgl_bayar')->orderBy('id');
                },
            ])
            ->orderByRaw('CASE WHEN sisa_piutang > 0 THEN 0 ELSE 1 END')
            ->orderBy('jatuh_tempo')
            ->orderByDesc('id')
            ->get();
    }

    private function buildReceivablesSpreadsheet($debts, string $search): Xlsx
    {
        $spreadsheet = new Spreadsheet();

        $this->fillDebtSummarySheet($spreadsheet->getActiveSheet(), $debts, $search);
        $this->fillDebtItemSheet($spreadsheet->createSheet(), $debts);
        $this->fillPaymentHistorySheet($spreadsheet->createSheet(), $debts);

        $spreadsheet->setActiveSheetIndex(0);

        return new Xlsx($spreadsheet);
    }

    private function fillDebtSummarySheet($sheet, $debts, string $search): void
    {
        $sheet->setTitle('Detail Piutang');
        $sheet->setCellValue('A1', 'Detail Piutang per Transaksi');
        $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Filter Search: ' . ($search !== '' ? $search : 'Semua'));

        $headings = [
            'No',
            'ID Transaksi',
            'No Invoice',
            'Tanggal Transaksi',
            'Tipe Pelanggan',
            'Kode Customer',
            'Nama Customer',
            'Alamat',
            'Sales',
            'Jatuh Tempo',
            'Total Piutang Awal',
            'Total Dibayar',
            'Sisa Piutang',
            'Status',
            'Keterangan Jatuh Tempo',
        ];

        $this->writeHeader($sheet, $headings, 5);

        $row = 6;
        foreach ($debts as $index => $item) {
            $totalPaid = (int) $item->receivablePayments->sum('nominal');
            $balance = (int) ($item->sisa_piutang ?? 0);

            $sheet->fromArray([
                $index + 1,
                (int) $item->id,
                $item->no_invoice,
                optional($item->tgl_transaksi)->format('Y-m-d H:i:s') ?? '-',
                $item->tipe_pelanggan ?? '-',
                optional($item->customer)->kode_cuts ?? '',
                $item->nama_pelanggan,
                optional($item->customer)->alamat_lengkap ?? ($item->alamat_pelanggan ?? ''),
                optional($item->sales)->nama_sales ?? '-',
                optional($item->jatuh_tempo)->format('Y-m-d') ?? '-',
                (int) $item->total_belanja,
                $totalPaid,
                $balance,
                $balance <= 0 ? 'Lunas' : 'Belum Lunas',
                $this->dueStatus($item->jatuh_tempo, $balance),
            ], null, 'A' . $row);

            $row++;
        }

        $this->styleTable($sheet, count($headings), 5, max(5, $row - 1), ['K', 'L', 'M']);
    }

    private function fillDebtItemSheet($sheet, $debts): void
    {
        $sheet->setTitle('Detail Item');
        $sheet->setCellValue('A1', 'Detail Item per Transaksi Piutang');

        $headings = [
            'No',
            'ID Transaksi',
            'No Invoice',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Qty',
            'Harga Satuan',
            'Subtotal',
            'Sisa Piutang Transaksi',
            'Status Piutang',
        ];

        $this->writeHeader($sheet, $headings, 3);

        $row = 4;
        $no = 1;
        foreach ($debts as $item) {
            $balance = (int) ($item->sisa_piutang ?? 0);
            foreach ($item->details as $detail) {
                $sheet->fromArray([
                    $no++,
                    (int) $item->id,
                    $item->no_invoice,
                    $detail->kode_barang,
                    optional($detail->product)->nama_barang ?? $detail->kode_barang,
                    optional($detail->product)->satuan ?? '',
                    (int) $detail->qty,
                    (int) $detail->harga_jual,
                    (int) $detail->subtotal,
                    $balance,
                    $balance <= 0 ? 'Lunas' : 'Belum Lunas',
                ], null, 'A' . $row);

                $row++;
            }
        }

        if ($row === 4) {
            $sheet->fromArray([1, '-', '-', '-', 'Tidak ada detail item', '-', 0, 0, 0, 0, '-'], null, 'A4');
            $row = 5;
        }

        $this->styleTable($sheet, count($headings), 3, max(3, $row - 1), ['G', 'H', 'I', 'J']);
    }

    private function fillPaymentHistorySheet($sheet, $debts): void
    {
        $sheet->setTitle('Histori Cicilan');
        $sheet->setCellValue('A1', 'Riwayat Pembayaran Cicilan Piutang');

        $headings = [
            'No',
            'ID Transaksi',
            'No Invoice',
            'Nama Customer',
            'Tanggal Bayar',
            'Waktu Catat Pembayaran',
            'Nominal Bayar',
            'Penerima Kasir',
            'Sisa Setelah Bayar',
            'Status Setelah Bayar',
            'Sisa Piutang Saat Ini',
        ];

        $this->writeHeader($sheet, $headings, 3);

        $row = 4;
        $no = 1;
        foreach ($debts as $item) {
            $runningBalance = (int) $item->total_belanja;
            $payments = $item->receivablePayments->sortBy(function ($payment): string {
                return (optional($payment->tgl_bayar)->format('Y-m-d') ?? '0000-00-00') . '-' . str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);
            })->values();

            if ($payments->isEmpty()) {
                $sheet->fromArray([
                    $no++,
                    (int) $item->id,
                    $item->no_invoice,
                    $item->nama_pelanggan,
                    '-',
                    '-',
                    0,
                    '-',
                    (int) $item->sisa_piutang,
                    (int) $item->sisa_piutang <= 0 ? 'Lunas' : 'Belum Lunas',
                    (int) $item->sisa_piutang,
                ], null, 'A' . $row);
                $row++;
                continue;
            }

            foreach ($payments as $payment) {
                $runningBalance = max(0, $runningBalance - (int) $payment->nominal);

                $sheet->fromArray([
                    $no++,
                    (int) $item->id,
                    $item->no_invoice,
                    $item->nama_pelanggan,
                    optional($payment->tgl_bayar)->format('Y-m-d') ?? '-',
                    optional($payment->created_at)->format('Y-m-d H:i:s') ?? '-',
                    (int) $payment->nominal,
                    $payment->penerima_kasir ?? '-',
                    $runningBalance,
                    $runningBalance <= 0 ? 'Lunas' : 'Belum Lunas',
                    (int) $item->sisa_piutang,
                ], null, 'A' . $row);
                $row++;
            }
        }

        if ($row === 4) {
            $sheet->fromArray([1, '-', '-', 'Tidak ada piutang', '-', '-', 0, '-', 0, '-', 0], null, 'A4');
            $row = 5;
        }

        $this->styleTable($sheet, count($headings), 3, max(3, $row - 1), ['G', 'I', 'K']);
    }

    private function writeHeader($sheet, array $headings, int $headerRow): void
    {
        foreach ($headings as $i => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $heading);
        }
    }

    private function styleTable($sheet, int $headingCount, int $headerRow, int $lastRow, array $numberColumns = []): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($headingCount);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        foreach ($numberColumns as $column) {
            $sheet->getStyle($column . ($headerRow + 1) . ':' . $column . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->freezePane('A' . ($headerRow + 1));

        for ($column = 1; $column <= $headingCount; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function dueStatus($date, int $balance): string
    {
        if ($balance <= 0) {
            return 'Lunas';
        }

        if (! $date) {
            return 'Tanpa jatuh tempo';
        }

        $diff = Carbon::today()->diffInDays(Carbon::parse($date), false);

        if ($diff < 0) {
            return abs($diff) . ' hari lewat jatuh tempo';
        }

        if ($diff === 0) {
            return 'Jatuh tempo hari ini';
        }

        return $diff . ' hari lagi';
    }

    private function desktopExportPath(DesktopBridge $desktop, string $title, string $filename, string $extension): ?string
    {
        $settings = $this->settingsMap();
        $folder = $desktop->selectExportFolder($title, $settings['data_directory'] ?? null);

        if (! is_string($folder) || trim($folder) === '') {
            return null;
        }

        $folder = rtrim($folder, DIRECTORY_SEPARATOR);
        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException('Folder export tidak dapat dibuat: ' . $folder);
        }

        return $this->ensureExtension($folder . DIRECTORY_SEPARATOR . $filename, $extension);
    }

    private function exportPath(string $filename): string
    {
        $directory = storage_path('app/exports');

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak dapat dibuat.');
        }

        return $directory . DIRECTORY_SEPARATOR . $filename;
    }

    private function ensureExtension(string $path, string $extension): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === strtolower($extension)
            ? $path
            : $path . '.' . $extension;
    }

    private function settingsMap(): array
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return [];
        }

        return DB::table('tbl_pengaturan')
            ->pluck('nilai_pengaturan', 'kunci_pengaturan')
            ->toArray();
    }

    private function saveSetting(string $key, ?string $value): void
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return;
        }

        DB::table('tbl_pengaturan')->updateOrInsert(
            ['kunci_pengaturan' => $key],
            [
                'nilai_pengaturan' => $value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
// PATCH XMLWRITER FREE RECEIVABLE EXPORT START
    private function buildReceivablesExcelHtmlNoXml($debts, string $search): string
    {
        $html = $this->receivableExcelHtmlStart('Detail Piutang');
        $html .= '<h2>Detail Piutang per Transaksi</h2>';
        $html .= '<p>Tanggal Export: ' . $this->receivableExcelEscape(now()->format('Y-m-d H:i:s')) . '</p>';
        $html .= '<p>Filter Search: ' . $this->receivableExcelEscape($search !== '' ? $search : 'Semua') . '</p>';

        $html .= $this->receivableTableHtml([
            'No',
            'ID Transaksi',
            'No Invoice',
            'Tanggal Transaksi',
            'Jatuh Tempo',
            'Kode Customer',
            'Nama Customer',
            'Alamat',
            'Sales',
            'Total Hutang Awal',
            'Total Dibayar',
            'Sisa Piutang',
            'Status',
        ], $this->receivableDebtRows($debts));

        $html .= '<br><h2>Detail Item per Transaksi</h2>';
        $html .= $this->receivableTableHtml([
            'No',
            'No Invoice',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Qty',
            'Harga Satuan',
            'Subtotal',
        ], $this->receivableItemRows($debts));

        $html .= '<br><h2>Riwayat Pembayaran Cicilan</h2>';
        $html .= $this->receivableTableHtml([
            'No',
            'No Invoice',
            'Nama Customer',
            'Tanggal Bayar Cicilan',
            'Waktu Catat Pembayaran',
            'Nominal Bayar',
            'Penerima Kasir',
            'Sisa Setelah Bayar',
            'Status Setelah Bayar',
            'Sisa Piutang Saat Ini',
        ], $this->receivablePaymentRows($debts));

        return $html . $this->receivableExcelHtmlEnd();
    }

    private function receivableDebtRows($debts): array
    {
        $rows = [];
        $no = 1;

        foreach ($debts as $debt) {
            $totalPaid = (int) $debt->receivablePayments->sum('nominal');
            $balance = (int) $debt->sisa_piutang;
            $rows[] = [
                $no++,
                (int) $debt->id,
                $debt->no_invoice,
                optional($debt->tgl_transaksi)->format('Y-m-d H:i:s') ?? '',
                optional($debt->jatuh_tempo)->format('Y-m-d') ?? '',
                optional($debt->customer)->kode_cuts ?? '',
                $debt->nama_pelanggan,
                optional($debt->customer)->alamat_lengkap ?? ($debt->alamat_pelanggan ?? ''),
                optional($debt->sales)->nama_sales ?? '-',
                (int) $debt->total_belanja,
                $totalPaid,
                $balance,
                $balance <= 0 ? 'Lunas' : 'Belum Lunas',
            ];
        }

        return $rows !== [] ? $rows : [[1, '-', '-', '-', '-', '-', 'Tidak ada piutang', '-', '-', 0, 0, 0, '-']];
    }

    private function receivableItemRows($debts): array
    {
        $rows = [];
        $no = 1;

        foreach ($debts as $debt) {
            foreach ($debt->details as $detail) {
                $product = $detail->product;
                $rows[] = [
                    $no++,
                    $debt->no_invoice,
                    $detail->kode_barang,
                    optional($product)->nama_barang ?? $detail->kode_barang,
                    optional($product)->satuan ?? '',
                    (int) $detail->qty,
                    (int) $detail->harga_jual,
                    (int) $detail->subtotal,
                ];
            }
        }

        return $rows !== [] ? $rows : [[1, '-', '-', 'Tidak ada detail item', '-', 0, 0, 0]];
    }

    private function receivablePaymentRows($debts): array
    {
        $rows = [];
        $no = 1;

        foreach ($debts as $debt) {
            $runningBalance = (int) $debt->total_belanja;

            foreach ($debt->receivablePayments as $payment) {
                $runningBalance = max(0, $runningBalance - (int) $payment->nominal);
                $rows[] = [
                    $no++,
                    $debt->no_invoice,
                    $debt->nama_pelanggan,
                    optional($payment->tgl_bayar)->format('Y-m-d') ?? '',
                    optional($payment->created_at)->format('Y-m-d H:i:s') ?? '',
                    (int) $payment->nominal,
                    $payment->penerima_kasir ?? '-',
                    $runningBalance,
                    $runningBalance <= 0 ? 'Lunas' : 'Belum Lunas',
                    (int) $debt->sisa_piutang,
                ];
            }
        }

        return $rows !== [] ? $rows : [[1, '-', '-', '-', '-', 0, '-', 0, '-', 0]];
    }

    private function receivableTableHtml(array $headings, array $rows): string
    {
        $html = '<table border="1"><thead><tr>';

        foreach ($headings as $heading) {
            $html .= '<th>' . $this->receivableExcelEscape($heading) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $this->receivableExcelEscape($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function saveOrDownloadReceivableHtmlExcel(Request $request, DesktopBridge $desktop, string $html, string $filename, string $message, string $dialogTitle)
    {
        if ($desktop->available()) {
            $targetPath = $this->chooseReceivableDesktopExportPath($request, $desktop, $filename, 'xls', $dialogTitle);

            if ($targetPath instanceof RedirectResponse) {
                return $targetPath;
            }

            file_put_contents($targetPath, $html);
            $this->saveSetting('export_directory', dirname($targetPath));

            return redirect()
                ->route('receivables.index', $request->query())
                ->with('success', $message . ': ' . $targetPath);
        }

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function chooseReceivableDesktopExportPath(Request $request, DesktopBridge $desktop, string $filename, string $extension, string $dialogTitle)
    {
        $settings = $this->settingsMap();
        $defaultDirectory = $settings['export_directory'] ?? ($settings['data_directory'] ?? null);

        $targetDirectory = $desktop->selectExportDirectory($defaultDirectory, $dialogTitle);

        if (! $targetDirectory) {
            return redirect()
                ->route('receivables.index', $request->query())
                ->withErrors(['export' => 'Export dibatalkan. Folder penyimpanan belum dipilih.']);
        }

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            return redirect()
                ->route('receivables.index', $request->query())
                ->withErrors(['export' => 'Folder penyimpanan export tidak dapat dibuat.']);
        }

        return $this->ensureExtension(rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename, $extension);
    }

    private function receivableExcelHtmlStart(string $title): string
    {
        return "\xEF\xBB\xBF" . '<!doctype html><html><head><meta charset="UTF-8"><title>' . $this->receivableExcelEscape($title) . '</title><style>body{font-family:Arial,sans-serif;font-size:12px;color:#000;background:#fff}table{border-collapse:collapse;margin-bottom:12px}th,td{border:1px solid #000;padding:5px;vertical-align:top}th{font-weight:bold;background:#fff;color:#000}h2,p{margin:0 0 8px 0}</style></head><body>';
    }

    private function receivableExcelHtmlEnd(): string
    {
        return '</body></html>';
    }

    private function receivableExcelEscape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    // PATCH XMLWRITER FREE RECEIVABLE EXPORT END


}
