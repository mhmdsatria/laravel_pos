<?php

namespace App\Http\Controllers;

use App\Services\DesktopBridge;

use Illuminate\Support\Facades\Response;use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InventoryReportController extends Controller
{
    // PHASE02_INVENTORY_INDEX_START
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $onlyOutOfStock = $request->boolean('out_of_stock');
        $now = now();

        $productsQuery = $this->buildInventoryBaseQuery();
        $this->applyInventoryFilters($productsQuery, $request);

        $products = $productsQuery
            ->orderBy(Schema::hasColumn('tbl_barang', 'nama_barang') ? 'nama_barang' : 'id')
            ->paginate(15)
            ->withQueryString();

        $inventorySummary = $this->buildInventorySummary($request);

        return view('pages.inventory_report', [
            'products' => $products,
            'search' => $search,
            'onlyOutOfStock' => $onlyOutOfStock,
            'totalJenisBarang' => $inventorySummary['total_jenis_barang'],
            'totalStokTersedia' => $inventorySummary['total_stok_tersedia'],
            'totalStokKritis' => $inventorySummary['total_stok_kritis'],
            'inventorySummary' => $inventorySummary,
            'lastUpdate' => $now,
        ]);
    }
    // PHASE02_INVENTORY_INDEX_END

    public function show(int $id): JsonResponse
    {
        $product = DB::table('tbl_barang')->where('id', $id)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $supplierName = $this->resolveSupplierName($product);
        $rackLocation = $this->resolveRackLocation($product);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'kode_barang' => $product->kode_barang ?? '-',
                'nama_barang' => $product->nama_barang ?? '-',
                'deskripsi' => ($product->deskripsi ?? null) ?: 'Belum ada deskripsi barang.',
                'satuan' => $product->satuan ?? '-',
                'supplier_vendor' => $supplierName,
                'limit_minimum_stok' => (float) ($product->limit_minimum_stok ?? 0),
                'sisa_stok' => (float) ($product->sisa_stok ?? 0),
                'stok_awal' => (float) ($product->stok_awal ?? 0),
                'lokasi_rak' => $rackLocation,
                'harga_beli_terakhir' => (float) ($product->harga_beli_terakhir ?? 0),
                'harga_jual_normal' => (float) ($product->harga_jual_normal ?? 0),
                'updated_at' => $product->updated_at ? date('d M Y H:i', strtotime((string) $product->updated_at)) : '-',
            ],
        ]);
    }


    // PHASE02_INVENTORY_HELPERS_START
    private function buildInventoryBaseQuery()
    {
        return DB::table('tbl_barang')
            ->select($this->availableProductColumns([
                'id',
                'kode_barang',
                'nama_barang',
                'deskripsi',
                'satuan',
                'stok_awal',
                'sisa_stok',
                'limit_minimum_stok',
                'harga_beli_terakhir',
                'harga_jual_normal',
                'updated_at',
            ]));
    }

    private function buildInventorySummary(Request $request): array
    {
        $query = DB::table('tbl_barang');
        $this->applyInventoryFilters($query, $request);

        $totalJenisBarang = (int) (clone $query)->count('id');
        $totalStokTersedia = Schema::hasColumn('tbl_barang', 'sisa_stok')
            ? (float) (clone $query)->sum('sisa_stok')
            : 0;

        $totalStokKritis = 0;
        if (Schema::hasColumn('tbl_barang', 'sisa_stok') && Schema::hasColumn('tbl_barang', 'limit_minimum_stok')) {
            $totalStokKritis = (int) (clone $query)
                ->whereColumn('sisa_stok', '<=', 'limit_minimum_stok')
                ->count('id');
        }

        $totalNilaiModal = 0;
        if (Schema::hasColumn('tbl_barang', 'sisa_stok') && Schema::hasColumn('tbl_barang', 'harga_beli_terakhir')) {
            $totalNilaiModal = (float) ((clone $query)
                ->selectRaw('SUM(COALESCE(sisa_stok, 0) * COALESCE(harga_beli_terakhir, 0)) AS total_nilai')
                ->value('total_nilai') ?? 0);
        }

        $totalNilaiJual = 0;
        if (Schema::hasColumn('tbl_barang', 'sisa_stok') && Schema::hasColumn('tbl_barang', 'harga_jual_normal')) {
            $totalNilaiJual = (float) ((clone $query)
                ->selectRaw('SUM(COALESCE(sisa_stok, 0) * COALESCE(harga_jual_normal, 0)) AS total_nilai')
                ->value('total_nilai') ?? 0);
        }

        return [
            'total_jenis_barang' => $totalJenisBarang,
            'total_stok_tersedia' => $totalStokTersedia,
            'total_stok_kritis' => $totalStokKritis,
            'total_nilai_modal' => $totalNilaiModal,
            'total_nilai_jual' => $totalNilaiJual,
            'is_filtered' => trim((string) $request->query('search', '')) !== '' || $request->boolean('out_of_stock'),
        ];
    }
    // PHASE02_INVENTORY_HELPERS_END

    private function inventoryExportRows(Request $request)
    {
        $query = DB::table('tbl_barang')
            ->select($this->availableProductColumns([
                'id',
                'kode_barang',
                'nama_barang',
                'deskripsi',
                'satuan',
                'stok_awal',
                'sisa_stok',
                'limit_minimum_stok',
                'harga_beli_terakhir',
                'harga_jual_normal',
                'updated_at',
            ]));

        $this->applyInventoryFilters($query, $request);

        return $query
            ->orderBy(Schema::hasColumn('tbl_barang', 'nama_barang') ? 'nama_barang' : 'id')
            ->get();
    }

    private function applyInventoryFilters($query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search): void {
                if (Schema::hasColumn('tbl_barang', 'kode_barang')) {
                    $innerQuery->where('kode_barang', 'like', '%' . $search . '%');
                }

                if (Schema::hasColumn('tbl_barang', 'nama_barang')) {
                    $innerQuery->orWhere('nama_barang', 'like', '%' . $search . '%');
                }
            });
        }

        if ($request->boolean('out_of_stock') && Schema::hasColumn('tbl_barang', 'sisa_stok')) {
            $query->where(function ($stockQuery): void {
                $stockQuery->whereNull('sisa_stok')->orWhere('sisa_stok', '<=', 0);
            });
        }
    }

    private function availableProductColumns(array $columns): array
    {
        $available = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn('tbl_barang', $column)) {
                $available[] = $column;
            }
        }

        return $available ?: ['*'];
    }

    private function buildInventoryXlsx($rows, string $filenameBase): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Stok');

        $headers = $this->inventoryExportHeaders();
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        $sheet->setCellValue('A1', 'LAPORAN STOK BARANG');
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Tanggal Export: ' . now()->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '4', $header);
        }

        $sheet->getStyle('A4:' . $lastColumn . '4')->getFont()->setBold(true);
        $sheet->getStyle('A4:' . $lastColumn . '4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:' . $lastColumn . '4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $rowIndex = 5;
        $totalStok = 0;
        $totalNilaiModal = 0;
        $totalNilaiJual = 0;

        foreach ($rows as $index => $row) {
            $prepared = $this->preparedInventoryRow($row, $index + 1);
            $totalStok += (float) ($row->sisa_stok ?? 0);
            $totalNilaiModal += $prepared['nilai_stok_modal'];
            $totalNilaiJual += $prepared['nilai_stok_jual'];

            $values = array_values($prepared);
            foreach ($values as $colIndex => $value) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $rowIndex, $value);
            }
            $rowIndex++;
        }

        $summaryRow = $rowIndex + 1;
        $sheet->setCellValue('A' . $summaryRow, 'TOTAL SKU');
        $sheet->setCellValue('B' . $summaryRow, $rows->count());
        $sheet->setCellValue('H' . $summaryRow, 'TOTAL STOK');
        $sheet->setCellValue('I' . $summaryRow, format_qty($totalStok));
        $sheet->setCellValue('L' . $summaryRow, 'TOTAL MODAL');
        $sheet->setCellValue('M' . $summaryRow, $totalNilaiModal);
        $sheet->setCellValue('N' . $summaryRow, 'TOTAL JUAL');
        $sheet->setCellValue('O' . $summaryRow, $totalNilaiJual);
        $sheet->getStyle('A' . $summaryRow . ':' . $lastColumn . $summaryRow)->getFont()->setBold(true);

        $lastDataRow = max(4, $rowIndex - 1);
        $sheet->getStyle('A4:' . $lastColumn . $lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A' . $summaryRow . ':' . $lastColumn . $summaryRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:' . $lastColumn . '4');

        for ($column = 1; $column <= count($headers); $column++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $dir = sys_get_temp_dir() . '\\toko-bangunan-exports';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('Folder export tidak bisa dibuat: ' . $dir);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filenameBase . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function buildInventoryExcelHtml($rows): string
    {
        return "\xEF\xBB\xBF" . $this->buildInventoryTableHtml($rows, true);
    }

    private function buildInventoryPdfHtml($rows): string
    {
        return $this->buildInventoryTableHtml($rows, false);
    }

    private function buildInventoryTableHtml($rows, bool $excelMode): string
    {
        $headers = $this->inventoryExportHeaders();
        $totalStok = 0;
        $totalNilaiModal = 0;
        $totalNilaiJual = 0;
        $body = '';

        foreach ($rows as $index => $row) {
            $prepared = $this->preparedInventoryRow($row, $index + 1);
            $totalStok += (float) ($row->sisa_stok ?? 0);
            $totalNilaiModal += $prepared['nilai_stok_modal'];
            $totalNilaiJual += $prepared['nilai_stok_jual'];

            $body .= '<tr>';
            foreach ($prepared as $value) {
                $body .= '<td>' . e((string) $value) . '</td>';
            }
            $body .= '</tr>';
        }

        $headerHtml = '';
        foreach ($headers as $header) {
            $headerHtml .= '<th>' . e($header) . '</th>';
        }

        $title = 'Laporan Stok Barang';
        $date = now()->format('d/m/Y H:i:s');
        $summaryColspan = max(1, count($headers) - 6);

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#000;background:#fff;}
            h1{font-size:18px;text-align:center;margin:0 0 4px 0;}
            .meta{text-align:center;margin-bottom:12px;}
            table{width:100%;border-collapse:collapse;}
            th,td{border:1px solid #000;padding:5px;vertical-align:top;}
            th{font-weight:bold;text-align:center;background:#fff;color:#000;}
            .summary td{font-weight:bold;}
        </style></head><body>
            <h1>' . e($title) . '</h1>
            <div class="meta">Tanggal Export: ' . e($date) . '</div>
            <table>
                <thead><tr>' . $headerHtml . '</tr></thead>
                <tbody>' . $body . '
                    <tr class="summary">
                        <td colspan="2">TOTAL SKU</td><td>' . e((string) $rows->count()) . '</td>
                        <td colspan="' . $summaryColspan . '"></td>
                        <td>TOTAL STOK</td><td>' . e(format_qty($totalStok)) . '</td>
                        <td>TOTAL MODAL</td><td>' . e((string) $totalNilaiModal) . '</td>
                        <td>TOTAL JUAL</td><td>' . e((string) $totalNilaiJual) . '</td>
                    </tr>
                </tbody>
            </table>
        </body></html>';
    }

    private function inventoryExportHeaders(): array
    {
        return [
            'No',
            'ID',
            'Kode Barang',
            'Nama Barang',
            'Deskripsi',
            'Satuan',
            'Stok Awal',
            'Stok Saat Ini',
            'Limit Minimum',
            'Status Stok',
            'Harga Modal',
            'Harga Jual Normal',
            'Nilai Stok Modal',
            'Nilai Stok Jual',
            'Terakhir Update',
        ];
    }

    private function preparedInventoryRow(object $product, int $number): array
    {
        $stokAwal = (float) ($product->stok_awal ?? 0);
        $stokSaatIni = (float) ($product->sisa_stok ?? 0);
        $limitMinimum = (float) ($product->limit_minimum_stok ?? 0);
        $hargaModal = (float) ($product->harga_beli_terakhir ?? 0);
        $hargaJualNormal = (float) ($product->harga_jual_normal ?? 0);
        $nilaiStokModal = $stokSaatIni * $hargaModal;
        $nilaiStokJual = $stokSaatIni * $hargaJualNormal;

        if ($stokSaatIni <= 0) {
            $status = 'HABIS';
        } elseif ($limitMinimum > 0 && $stokSaatIni <= $limitMinimum) {
            $status = 'KRITIS';
        } else {
            $status = 'TERSEDIA';
        }

        return [
            'no' => $number,
            'id' => $product->id ?? '',
            'kode_barang' => $product->kode_barang ?? '',
            'nama_barang' => $product->nama_barang ?? '',
            'deskripsi' => $product->deskripsi ?? '',
            'satuan' => $product->satuan ?? '',
            'stok_awal' => format_qty($stokAwal),
            'stok_saat_ini' => format_qty($stokSaatIni),
            'limit_minimum' => format_qty($limitMinimum),
            'status_stok' => $status,
            'harga_modal' => $hargaModal,
            'harga_jual_normal' => $hargaJualNormal,
            'nilai_stok_modal' => $nilaiStokModal,
            'nilai_stok_jual' => $nilaiStokJual,
            'terakhir_update' => $product->updated_at ? date('d/m/Y H:i', strtotime((string) $product->updated_at)) : '-',
        ];
    }

    private function resolveSupplierName(object $product): string
    {
        foreach (['nama_supplier', 'supplier', 'vendor'] as $field) {
            if (isset($product->{$field}) && $product->{$field}) {
                return (string) $product->{$field};
            }
        }

        if (isset($product->supplier_id) && $product->supplier_id && Schema::hasTable('tbl_supplier')) {
            $supplier = DB::table('tbl_supplier')->where('id', $product->supplier_id)->first();
            if ($supplier && isset($supplier->nama_supplier)) {
                return (string) $supplier->nama_supplier;
            }
        }

        return 'Belum ditentukan';
    }

    private function resolveRackLocation(object $product): string
    {
        foreach (['lokasi_rak', 'rak', 'lokasi', 'gudang_rak'] as $field) {
            if (isset($product->{$field}) && $product->{$field}) {
                return (string) $product->{$field};
            }
        }

        return 'Belum ditentukan';
    }

    private function simplifiedInventoryRows(Request $request)
    {
        $query = \App\Models\Product::query()
            ->select([
                'id',
                'kode_barang',
                'nama_barang',
                'satuan',
                'sisa_stok',
                'limit_minimum_stok',
            ])
            ->orderBy('nama_barang');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }

        if ((string) $request->query('out_of_stock', '') === '1') {
            $query->where(function ($q) {
                $q->whereNull('sisa_stok')->orWhere('sisa_stok', '<=', 0);
            });
        }

        return $query->get()->map(function ($item) {
            $stock = (float) ($item->sisa_stok ?? 0);
            $limit = (float) ($item->limit_minimum_stok ?? 0);

            if ($stock <= 0) {
                $status = 'STOK HABIS';
            } elseif ($limit > 0 && $stock <= $limit) {
                $status = 'KRITIS';
            } else {
                $status = 'TERSEDIA';
            }

            return [
                'kode_barang' => (string) ($item->kode_barang ?? '-'),
                'nama_barang' => (string) ($item->nama_barang ?? '-'),
                'satuan' => (string) ($item->satuan ?? '-'),
                'stok_saat_ini' => format_qty($stock),
                'status_stok' => $status,
            ];
        });
    }
    private function getSafeExportDirectory(?DesktopBridge $desktop = null): string
    {
        // 1. Jika DesktopBridge tersedia
        if ($desktop && method_exists($desktop, 'available') && $desktop->available()) {
            $settings = Schema::hasTable('tbl_pengaturan')
                ? DB::table('tbl_pengaturan')->pluck('nilai_pengaturan', 'kunci_pengaturan')->toArray()
                : [];

            $dir = $settings['export_directory']
                ?? $settings['data_directory']
                ?? null;

            if ($dir && is_dir($dir)) {
                return $dir;
            }

            if ($dir && !is_dir($dir)) {
                @mkdir($dir, 0775, true);
                return $dir;
            }
        }

        // 2. Fallback Windows User Documents
        $fallback = getenv('USERPROFILE') . '\\Documents\\TokoBangunan\\Exports';

        if (!is_dir($fallback)) {
            @mkdir($fallback, 0775, true);
        }

        return $fallback;
    }
    private function buildExcelHtmlReport(array $filters, $rows): string
{
    $html = '<html><head><meta charset="UTF-8">';
    $html .= '<style>
        body { font-family: Arial; font-size: 12px; }
        h2 { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #1f2937; color: #fff; padding: 8px; font-size: 11px; }
        td { border: 1px solid #ddd; padding: 6px; font-size: 11px; }
        tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style></head><body>';

    $html .= "<h2>LAPORAN PENJUALAN</h2>";
    $html .= "<p>Periode: {$filters['startDate']} s/d {$filters['endDate']}</p>";
    $html .= "<p>Kategori: " . strtoupper($filters['kategoriPelanggan']) . "</p>";
    $html .= "<p>Metode: " . strtoupper($filters['metodeBayar']) . "</p>";

    $html .= '<table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Metode</th>
                <th class="right">Total Penjualan</th>
                <th class="right">Total Modal</th>
                <th class="right">Laba</th>
            </tr>
        </thead><tbody>';

    foreach ($rows as $i => $row) {

        $omset = (float) $row->total_penjualan;
        $modal = (float) $row->total_modal;
        $laba  = (float) $row->laba_kotor;

        $html .= '<tr>
            <td class="center">'.($i+1).'</td>
            <td>'.$row->no_invoice.'</td>
            <td>'.$row->tgl_transaksi.'</td>
            <td>'.$row->nama_pelanggan.'</td>
            <td class="center">'.$row->metode_bayar.'</td>
            <td class="right">'.number_format($omset,0,',','.').'</td>
            <td class="right">'.number_format($modal,0,',','.').'</td>
            <td class="right">'.number_format($laba,0,',','.').'</td>
        </tr>';
    }

    $html .= '</tbody></table></body></html>';

    return $html;
}
    public function export_excel(Request $request)
{
    $rows = $this->inventoryExportRows($request);

    $filename = 'Laporan_Stok_' . now()->format('Ymd_His') . '.xls';

    $html = '<html><head><meta charset="UTF-8"></head><body>';
    $html .= '<table border="1">
    <tr>
        <th>No</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Deskripsi</th>
        <th>Satuan</th>
        <th>Stok Saat Ini</th>
        <th>Status Stok</th>
    </tr>';

    foreach ($rows as $i => $row) {

        $stok = (float) ($row->sisa_stok ?? 0);
        $limit = (float) ($row->limit_minimum_stok ?? 0);

        $status = $stok <= 0
            ? 'HABIS'
            : ($stok <= $limit ? 'KRITIS' : 'AMAN');

        $html .= '<tr>
            <td>'.($i+1).'</td>
            <td>'.$row->kode_barang.'</td>
            <td>'.$row->nama_barang.'</td>
            <td>'.$row->deskripsi.'</td>
            <td>'.$row->satuan.'</td>
            <td>'.format_qty($stok).'</td>
            <td>'.$status.'</td>
        </tr>';
    }

    $html .= '</table></body></html>';

    return response($html, 200, [
        'Content-Type' => 'application/vnd.ms-excel',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"'
    ]);
}
    public function export_pdf(Request $request)
    {
        $rows = $this->simplifiedInventoryRows($request);
        $printedAt = now()->format('d/m/Y H:i:s');

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('pages.inventory-report-pdf', [
                'rows' => $rows,
                'printedAt' => $printedAt,
            ])->setPaper('a4', 'portrait');

            return $pdf->download('laporan-stok-barang-' . now()->format('Ymd-His') . '.pdf');
        }

        return view('pages.inventory-report-pdf', [
            'rows' => $rows,
            'printedAt' => $printedAt,
        ]);
    }

}
