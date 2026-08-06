<?php

namespace App\Http\Controllers;

use App\Services\DesktopBridge;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{

    private function withoutRefundedSales($query, string $alias = '')
    {
        return $query;
    }

    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $databaseReady = Schema::hasTable('tbl_penjualan') && Schema::hasTable('tbl_penjualan_detail') && Schema::hasTable('tbl_barang');

        if (! $databaseReady) {
            return view('pages.reports', array_merge($filters, [
                'transactions' => collect(),
                'totalOmset' => 0,
                'totalModal' => 0,
                'totalLabaKotor' => 0,
                'averageMarginPercent' => 0,
                'totalLunas' => 0,
                'totalHutang' => 0,
                'databaseReady' => false,
            ]));
        }

        return view('pages.reports', array_merge($filters, $this->getReportData($filters), [
            'databaseReady' => true,
        ]));
    }

    public function exportPdf(Request $request, DesktopBridge $desktop)
    {
        try {
            $filters = $this->parseFilters($request);
            $reportData = $this->getReportData($filters);
            $filename = 'Laporan_Keuangan_' . $filters['startDate'] . '_to_' . $filters['endDate'] . '_' . now()->format('His') . '.pdf';

            $pdf = Pdf::loadView('pages.reports_pdf', array_merge($filters, $reportData))
                ->setPaper('a4', 'landscape');

            if ($desktop->available()) {
                $targetPath = $this->chooseDesktopExportPath(
                    $request,
                    $desktop,
                    $filename,
                    'pdf',
                    'reports.index',
                    'Pilih Folder Simpan PDF Laporan Keuangan'
                );

                if ($targetPath instanceof RedirectResponse) {
                    return $targetPath;
                }

                file_put_contents($targetPath, $pdf->output());
                $this->saveSetting('export_directory', dirname($targetPath));

                return redirect()
                    ->route('reports.index', $request->query())
                    ->with('success', 'PDF laporan keuangan berhasil disimpan: ' . $targetPath);
            }

            return $pdf->download($filename);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('reports.index', $request->query())
                ->withErrors(['export_pdf' => 'Gagal menyimpan PDF laporan keuangan: ' . $exception->getMessage()]);
        }
    }

    public function exportExcel(Request $request, DesktopBridge $desktop)
    {
        try {
            $filters = $this->parseFilters($request);
            $filename = 'Laporan_Keuangan_Penjualan_' . $filters['startDate'] . '_to_' . $filters['endDate'] . '_' . now()->format('His') . '.xls';
            $html = $this->buildReportExcelHtmlNoXml($filters);

            return $this->saveOrDownloadHtmlExcel(
                $request,
                $desktop,
                $html,
                $filename,
                'reports.index',
                'Excel laporan keuangan berhasil disimpan',
                'Pilih Folder Simpan Excel Laporan Keuangan'
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('reports.index', $request->query())
                ->withErrors(['export_excel' => 'Gagal menyimpan Excel laporan keuangan: ' . $exception->getMessage()]);
        }
    }

    
    // PATCH XMLWRITER FREE EXPORT START
    private function buildReportExcelHtmlNoXml(array $filters): string
    {
        $rows = $this->reportExcelRows($filters);

        $headings = [
            'ID',
            'No Struk',
            'Status Order',
            'Waktu Penjualan',
            'ID Sales',
            'Nama Sales',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Qty',
            'Harga Satuan',
            'Total Jual',
            'Harga Modal (Satuan)',
            'Total Modal',
            'Laba Kotor Item',
            'Tipe Pembayaran',
            'Kode Customer',
            'Nama Customer',
        ];

        $html = $this->excelHtmlStart('Laporan Keuangan / Penjualan');
        $html .= '<h2>Laporan Keuangan / Penjualan</h2>';
        $html .= '<p>Periode: ' . $this->excelEscape($filters['startDate'] . ' s.d. ' . $filters['endDate']) . '</p>';
        $html .= '<p>Kategori Pelanggan: ' . $this->excelEscape(strtoupper($filters['kategoriPelanggan'])) . '</p>';
        $html .= '<p>Metode Bayar: ' . $this->excelEscape(strtoupper($filters['metodeBayar'])) . '</p>';
        $html .= '<table border="1"><thead><tr>';

        foreach ($headings as $heading) {
            $html .= '<th>' . $this->excelEscape($heading) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        if ($rows->isEmpty()) {
            $html .= '<tr><td colspan="' . count($headings) . '">Tidak ada data penjualan.</td></tr>';
        }

        foreach ($rows as $item) {
            $qty = (int) ($item->qty ?? 0);
            $qtyTerkirim = (int) ($item->qty_terkirim ?? 0);
            $statusOrder = $qty > 0 && $qtyTerkirim >= $qty ? 'Terkirim' : 'Belum Terkirim Semua';
            $hargaJual    = (int) ($item->harga_jual ?? 0);
            $subtotal     = (int) ($item->subtotal ?? 0);
            $hargaModal   = (int) ($item->harga_beli_terakhir ?? 0);
            $totalModal   = $qty * $hargaModal;
            $labaItem     = $subtotal - $totalModal;

            $cells = [
                (int) $item->id,
                $item->no_invoice,
                $statusOrder,
                $item->tgl_transaksi ? Carbon::parse($item->tgl_transaksi)->format('d/m/Y') : '',
                $item->sales_id ? (int) $item->sales_id : '-',
                $item->nama_sales ?: '-',
                $item->kode_barang,
                $item->nama_barang ?? $item->kode_barang,
                $item->satuan ?? '',
                $qty,
                $hargaJual,
                $subtotal,
                $hargaModal,
                $totalModal,
                $labaItem,
                $item->metode_bayar,
                $item->kode_customer ?? '',
                $item->nama_pelanggan,
            ];

            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= '<td>' . $this->excelEscape($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= $this->excelHtmlEnd();

        return $html;
    }

    private function reportExcelRows(array $filters)
    {
        $hasQtyTerkirim = Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim');
        $hasQtyRefund   = Schema::hasColumn('tbl_penjualan_detail', 'qty_refund');
        $hasCustomerId  = Schema::hasColumn('tbl_penjualan', 'customer_id') && Schema::hasTable('tbl_customer');
        $hasSalesId     = Schema::hasColumn('tbl_penjualan', 'sales_id') && Schema::hasTable('tbl_sales');
        $hasHargaBeli   = Schema::hasColumn('tbl_barang', 'harga_beli_terakhir');

        $query = DB::table('tbl_penjualan as p')
            ->join('tbl_penjualan_detail as d', 'd.penjualan_id', '=', 'p.id')
            ->leftJoin('tbl_barang as b', 'b.kode_barang', '=', 'd.kode_barang');

        if ($hasCustomerId) {
            $query->leftJoin('tbl_customer as c', 'c.id', '=', 'p.customer_id');
        }

        if ($hasSalesId) {
            $query->leftJoin('tbl_sales as s', 's.id', '=', 'p.sales_id');
        }

        $this->withoutRefundedSales($query, 'p');

        $select = [
            'p.id',
            'p.no_invoice',
            'p.tgl_transaksi',
            'p.metode_bayar',
            'p.nama_pelanggan',
            'p.tipe_pelanggan',
            'p.sales_id',
            'd.kode_barang',
            DB::raw($hasQtyRefund
                ? 'CASE WHEN COALESCE(d.qty,0) > COALESCE(d.qty_refund,0) THEN COALESCE(d.qty,0) - COALESCE(d.qty_refund,0) ELSE 0 END as qty'
                : 'COALESCE(d.qty, 0) as qty'),
            'd.harga_jual',
            DB::raw($hasQtyRefund
                ? '(CASE WHEN COALESCE(d.qty,0) > COALESCE(d.qty_refund,0) THEN COALESCE(d.qty,0) - COALESCE(d.qty_refund,0) ELSE 0 END) * COALESCE(d.harga_jual,0) as subtotal'
                : 'COALESCE(d.subtotal, 0) as subtotal'),
            // Harga modal: ambil dari tbl_barang, fallback 0 jika null atau kolom tidak ada
            DB::raw($hasHargaBeli ? 'COALESCE(b.harga_beli_terakhir, 0) as harga_beli_terakhir' : '0 as harga_beli_terakhir'),
            'b.nama_barang',
            'b.satuan',
            DB::raw($hasQtyTerkirim ? 'COALESCE(d.qty_terkirim, 0) as qty_terkirim' : '0 as qty_terkirim'),
            DB::raw($hasSalesId ? "COALESCE(s.nama_sales, '') as nama_sales" : "'' as nama_sales"),
        ];

        if ($hasCustomerId) {
            $select[] = DB::raw("COALESCE(c.kode_cuts, '') as kode_customer");
        } else {
            $select[] = DB::raw("'' as kode_customer");
        }

        return $query->select($select)
            ->whereBetween(DB::raw('DATE(p.tgl_transaksi)'), [$filters['startDate'], $filters['endDate']])
            ->when($filters['kategoriPelanggan'] !== 'all', fn ($q) => $q->where('p.tipe_pelanggan', strtoupper($filters['kategoriPelanggan'])))
            ->when($filters['metodeBayar'] !== 'all', fn ($q) => $q->where('p.metode_bayar', strtoupper($filters['metodeBayar'])))
            ->orderByDesc('p.tgl_transaksi')
            ->orderByDesc('p.id')
            ->orderBy('d.id')
            ->get();
    }

    private function saveOrDownloadHtmlExcel(Request $request, DesktopBridge $desktop, string $html, string $filename, string $backRoute, string $message, string $dialogTitle)
    {
        if ($desktop->available()) {
            $targetPath = $this->chooseDesktopExportPath($request, $desktop, $filename, 'xls', $backRoute, $dialogTitle);

            if ($targetPath instanceof RedirectResponse) {
                return $targetPath;
            }

            file_put_contents($targetPath, $html);
            $this->saveSetting('export_directory', dirname($targetPath));

            return redirect()
                ->route($backRoute, $request->query())
                ->with('success', $message . ': ' . $targetPath);
        }

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function chooseDesktopExportPath(Request $request, DesktopBridge $desktop, string $filename, string $extension, string $backRoute, string $dialogTitle)
    {
        $settings = $this->settingsMap();
        $defaultDirectory = $settings['export_directory'] ?? ($settings['data_directory'] ?? null);

        $targetDirectory = $desktop->selectExportDirectory($defaultDirectory, $dialogTitle);

        if (! $targetDirectory) {
            return redirect()
                ->route($backRoute, $request->query())
                ->withErrors(['export' => 'Export dibatalkan. Folder penyimpanan belum dipilih.']);
        }

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            return redirect()
                ->route($backRoute, $request->query())
                ->withErrors(['export' => 'Folder penyimpanan export tidak dapat dibuat.']);
        }

        return $this->ensureExtension(rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename, $extension);
    }

    private function excelHtmlStart(string $title): string
    {
        return "\xEF\xBB\xBF" . '<!doctype html><html><head><meta charset="UTF-8"><title>' . $this->excelEscape($title) . '</title><style>body{font-family:Arial,sans-serif;font-size:12px;color:#000;background:#fff}table{border-collapse:collapse}th,td{border:1px solid #000;padding:5px;vertical-align:top}th{font-weight:bold;background:#fff;color:#000}h2,p{margin:0 0 8px 0}</style></head><body>';
    }

    private function excelHtmlEnd(): string
    {
        return '</body></html>';
    }

    private function excelEscape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    // PATCH XMLWRITER FREE EXPORT END

    private function getReportData(array $filters): array
    {
        $hasDetailRefund = Schema::hasColumn('tbl_penjualan_detail', 'qty_refund');
        $hasRefundTotal = Schema::hasColumn('tbl_penjualan', 'refund_total');
        $hasSalesTable  = Schema::hasTable('tbl_sales');
        $qtyNetExpression = $hasDetailRefund
            ? 'CASE WHEN COALESCE(d.qty, 0) > COALESCE(d.qty_refund, 0) THEN COALESCE(d.qty, 0) - COALESCE(d.qty_refund, 0) ELSE 0 END'
            : 'COALESCE(d.qty, 0)';
        $netSalesExpression = $hasRefundTotal
            ? 'CASE WHEN COALESCE(total_belanja, 0) > COALESCE(refund_total, 0) THEN COALESCE(total_belanja, 0) - COALESCE(refund_total, 0) ELSE 0 END'
            : 'COALESCE(total_belanja, 0)';
        $netSalesExpressionWithAlias = $hasRefundTotal
            ? 'CASE WHEN COALESCE(p.total_belanja, 0) > COALESCE(p.refund_total, 0) THEN COALESCE(p.total_belanja, 0) - COALESCE(p.refund_total, 0) ELSE 0 END'
            : 'COALESCE(p.total_belanja, 0)';
        $modalExpression = Schema::hasColumn('tbl_barang', 'harga_beli_terakhir')
            ? "{$qtyNetExpression} * COALESCE(b.harga_beli_terakhir, 0)"
            : '0';

        $ordersQuery = DB::table('tbl_penjualan')
            ->whereBetween(DB::raw('DATE(tgl_transaksi)'), [$filters['startDate'], $filters['endDate']]);

        $this->withoutRefundedSales($ordersQuery);

        if ($filters['kategoriPelanggan'] !== 'all') {
            $ordersQuery->where('tipe_pelanggan', strtoupper($filters['kategoriPelanggan']));
        }

        if ($filters['metodeBayar'] !== 'all') {
            $ordersQuery->where('metode_bayar', strtoupper($filters['metodeBayar']));
        }

        $totalOmset = (float) $ordersQuery->sum(DB::raw($netSalesExpression));

        $transactions = DB::table('tbl_penjualan as p')
            ->leftJoin('tbl_penjualan_detail as d', 'd.penjualan_id', '=', 'p.id')
            ->leftJoin('tbl_barang as b', 'b.kode_barang', '=', 'd.kode_barang');

        if ($hasSalesTable) {
            $transactions->leftJoin('tbl_sales as s', 's.id', '=', 'p.sales_id');
        }

        $this->withoutRefundedSales($transactions, 'p');

        $transactions = $transactions
            ->select([
                'p.id',
                'p.no_invoice',
                'p.tgl_transaksi',
                'p.nama_pelanggan',
                'p.tipe_pelanggan',
                'p.metode_bayar',
                'p.status',
                'p.sisa_piutang',
                'p.sales_id',
                DB::raw($hasSalesTable ? "COALESCE(s.nama_sales, '-') as nama_sales" : "'-' as nama_sales"),
                DB::raw($hasSalesTable ? "COALESCE(s.kode_sales, '') as kode_sales" : "'' as kode_sales"),
                DB::raw($netSalesExpressionWithAlias . ' as total_penjualan'),
                DB::raw("COALESCE(SUM({$modalExpression}), 0) as total_modal"),
                DB::raw("({$netSalesExpressionWithAlias} - COALESCE(SUM({$modalExpression}), 0)) as laba_kotor"),
                DB::raw("CASE WHEN {$netSalesExpressionWithAlias} > 0 THEN (({$netSalesExpressionWithAlias} - COALESCE(SUM({$modalExpression}), 0)) / {$netSalesExpressionWithAlias}) * 100 ELSE 0 END as margin_percent"),
                DB::raw('COUNT(d.id) as total_item_line'),
                DB::raw("COALESCE(SUM({$qtyNetExpression}), 0) as total_qty"),
            ])
            ->whereBetween(DB::raw('DATE(p.tgl_transaksi)'), [$filters['startDate'], $filters['endDate']])
            ->when($filters['kategoriPelanggan'] !== 'all', fn ($query) => $query->where('p.tipe_pelanggan', strtoupper($filters['kategoriPelanggan'])))
            ->when($filters['metodeBayar'] !== 'all', fn ($query) => $query->where('p.metode_bayar', strtoupper($filters['metodeBayar'])))
            ->groupBy(
                'p.id',
                'p.no_invoice',
                'p.tgl_transaksi',
                'p.nama_pelanggan',
                'p.tipe_pelanggan',
                'p.metode_bayar',
                'p.status',
                'p.sisa_piutang',
                'p.total_belanja',
                'p.refund_total',
                'p.sales_id',
                ...($hasSalesTable ? ['s.nama_sales', 's.kode_sales'] : [])
            )
            ->orderByDesc('p.tgl_transaksi')
            ->orderByDesc('p.id')
            ->get();

        $totalModal = (float) $transactions->sum('total_modal');
        $totalLabaKotor = (float) $transactions->sum('laba_kotor');

        return [
            'transactions' => $transactions,
            'totalOmset' => $totalOmset,
            'totalModal' => $totalModal,
            'totalLabaKotor' => $totalLabaKotor,
            'averageMarginPercent' => $totalOmset > 0 ? ($totalLabaKotor / $totalOmset) * 100 : 0,
            'totalLunas' => (float) $transactions->filter(fn ($item) => $item->metode_bayar === 'CASH' || (int) ($item->sisa_piutang ?? 0) === 0)->sum('total_penjualan'),
            'totalHutang' => (float) $transactions->filter(fn ($item) => $item->metode_bayar === 'TEMPO' && (int) ($item->sisa_piutang ?? 0) > 0)->sum('sisa_piutang'),
        ];
    }

    private function buildReportSpreadsheet(array $filters): Xlsx
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penjualan');

        $sheet->setCellValue('A1', 'Laporan Keuangan / Penjualan');
        $sheet->setCellValue('A2', 'Periode: ' . $filters['startDate'] . ' s.d. ' . $filters['endDate']);
        $sheet->setCellValue('A3', 'Kategori Pelanggan: ' . strtoupper($filters['kategoriPelanggan']));
        $sheet->setCellValue('A4', 'Metode Bayar: ' . strtoupper($filters['metodeBayar']));

        $headings = [
            'ID',
            'No Struk',
            'Status Order',
            'Waktu Penjualan',
            'ID Sales',
            'Nama Sales',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Qty',
            'Harga Satuan',
            'Total Jual',
            'Harga Modal (Satuan)',
            'Total Modal',
            'Laba Kotor Item',
            'Tipe Pembayaran',
            'Kode Customer',
            'Nama Customer',
        ];

        $headerRow = 6;
        foreach ($headings as $i => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $heading);
        }

        $hasQtyTerkirim = Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim');
        $hasCustomerId  = Schema::hasColumn('tbl_penjualan', 'customer_id') && Schema::hasTable('tbl_customer');
        $hasSalesId     = Schema::hasColumn('tbl_penjualan', 'sales_id') && Schema::hasTable('tbl_sales');

        $query = DB::table('tbl_penjualan as p')
            ->join('tbl_penjualan_detail as d', 'd.penjualan_id', '=', 'p.id')
            ->leftJoin('tbl_barang as b', 'b.kode_barang', '=', 'd.kode_barang');

        if ($hasCustomerId) {
            $query->leftJoin('tbl_customer as c', 'c.id', '=', 'p.customer_id');
        }

        if ($hasSalesId) {
            $query->leftJoin('tbl_sales as s', 's.id', '=', 'p.sales_id');
        }

        $this->withoutRefundedSales($query, 'p');

        $hasHargaBeli = Schema::hasColumn('tbl_barang', 'harga_beli_terakhir');

        $select = [
            'p.id',
            'p.no_invoice',
            'p.tgl_transaksi',
            'p.metode_bayar',
            'p.nama_pelanggan',
            'p.tipe_pelanggan',
            'p.sales_id',
            'd.kode_barang',
            'd.qty',
            'd.harga_jual',
            'd.subtotal',
            DB::raw($hasHargaBeli ? 'COALESCE(b.harga_beli_terakhir, 0) as harga_beli_terakhir' : '0 as harga_beli_terakhir'),
            'b.nama_barang',
            'b.satuan',
            DB::raw($hasQtyTerkirim ? 'COALESCE(d.qty_terkirim, 0) as qty_terkirim' : '0 as qty_terkirim'),
            DB::raw($hasSalesId ? "COALESCE(s.nama_sales, '') as nama_sales" : "'' as nama_sales"),
        ];

        if ($hasCustomerId) {
            $select[] = DB::raw("COALESCE(c.kode_cuts, '') as kode_customer");
        } else {
            $select[] = DB::raw("'' as kode_customer");
        }

        $rows = $query->select($select)
            ->whereBetween(DB::raw('DATE(p.tgl_transaksi)'), [$filters['startDate'], $filters['endDate']])
            ->when($filters['kategoriPelanggan'] !== 'all', fn ($q) => $q->where('p.tipe_pelanggan', strtoupper($filters['kategoriPelanggan'])))
            ->when($filters['metodeBayar'] !== 'all', fn ($q) => $q->where('p.metode_bayar', strtoupper($filters['metodeBayar'])))
            ->orderByDesc('p.tgl_transaksi')
            ->orderByDesc('p.id')
            ->orderBy('d.id')
            ->get();

        $row = $headerRow + 1;
        foreach ($rows as $item) {
            $qty         = (int) ($item->qty ?? 0);
            $qtyTerkirim = (int) ($item->qty_terkirim ?? 0);
            $statusOrder = $qty > 0 && $qtyTerkirim >= $qty ? 'Terkirim' : 'Belum Terkirim Semua';
            $hargaJual   = (int) ($item->harga_jual ?? 0);
            $subtotal    = (int) ($item->subtotal ?? 0);
            $hargaModal  = (int) ($item->harga_beli_terakhir ?? 0);
            $totalModal  = $qty * $hargaModal;
            $labaItem    = $subtotal - $totalModal;

            $sheet->fromArray([
                (int) $item->id,
                $item->no_invoice,
                $statusOrder,
                $item->tgl_transaksi ? Carbon::parse($item->tgl_transaksi)->format('d/m/Y') : '',
                $item->sales_id ? (int) $item->sales_id : '-',
                $item->nama_sales ?: '-',
                $item->kode_barang,
                $item->nama_barang ?? $item->kode_barang,
                $item->satuan ?? '',
                $qty,
                $hargaJual,
                $subtotal,
                $hargaModal,
                $totalModal,
                $labaItem,
                strtoupper((string) $item->metode_bayar) === 'TEMPO' ? 'Tempo' : 'Cash',
                $item->kode_customer ?: ($item->tipe_pelanggan === 'USER' ? 'USER' : ''),
                $item->nama_pelanggan,
            ], null, 'A' . $row);
            $row++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
        $lastRow = max($headerRow, $row - 1);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        // Kolom angka: H=Qty, I=Harga Satuan, J=Total Jual, K=Harga Modal, L=Total Modal, M=Laba Kotor
        $sheet->getStyle('H' . ($headerRow + 1) . ':M' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->freezePane('A' . ($headerRow + 1));

        for ($column = 1; $column <= count($headings); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        return new Xlsx($spreadsheet);
    }

    private function parseFilters(Request $request): array
    {
        $defaultStartDate = now()->toDateString();
        $defaultEndDate = now()->toDateString();
        $startDate = $this->normalizeDate($request->input('start_date'), $defaultStartDate);
        $endDate = $this->normalizeDate($request->input('end_date'), $defaultEndDate);

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $kategoriPelanggan = strtolower((string) $request->input('kategori_pelanggan', 'all'));
        $metodeBayar = strtolower((string) $request->input('metode_bayar', 'all'));

        return [
            'defaultStartDate' => $defaultStartDate,
            'defaultEndDate' => $defaultEndDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'kategoriPelanggan' => in_array($kategoriPelanggan, ['all', 'user', 'toko', 'sales'], true) ? $kategoriPelanggan : 'all',
            'metodeBayar' => in_array($metodeBayar, ['all', 'cash', 'tempo'], true) ? $metodeBayar : 'all',
        ];
    }

    private function normalizeDate(?string $date, string $fallback): string
    {
        try {
            return $date ? Carbon::parse($date)->toDateString() : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function exportWriter(Request $request, DesktopBridge $desktop, Xlsx $writer, string $filename, string $backRoute, string $message)
    {
        if ($desktop->shouldUseNativeDialogs($request)) {
            $targetPath = $this->desktopExportPath(
                $desktop,
                'Pilih Folder Penyimpanan Laporan Keuangan Excel',
                $filename,
                'xlsx'
            );

            if ($targetPath === null) {
                $path = $this->exportPath($filename);
                $writer->save($path);

                return response()->download($path, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }

            try {
                $writer->save($targetPath);
                $this->saveSetting('data_directory', dirname($targetPath));

                return redirect()
                    ->route($backRoute, $request->query())
                    ->with('success', $message . ': ' . $targetPath);
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route($backRoute, $request->query())
                    ->withErrors(['export_excel' => 'Gagal menyimpan Excel laporan keuangan: ' . $exception->getMessage()]);
            }
        }

        $path = $this->exportPath($filename);
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
            // Updated dynamically for 2026 application runtime compliance
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
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === strtolower($extension) ? $path : $path . '.' . $extension;
    }

    private function settingsMap(): array
    {
        return Schema::hasTable('tbl_pengaturan') ? DB::table('tbl_pengaturan')->pluck('nilai_pengaturan', 'kunci_pengaturan')->toArray() : [];
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
}