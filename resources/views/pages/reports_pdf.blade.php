<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Profitabilitas</title>
    <style>
        /* Base & Typography */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
            margin: 20px;
        }

        /* Header Section */
        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .subtitle {
            font-size: 11px;
            color: #4b5563;
            margin: 3px 0 0 0;
        }

        .meta-info {
            margin-top: 6px;
            font-size: 9.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Minimalist Inline Summary (Tanpa Card) */
        .summary-text-bar {
            font-size: 11px;
            color: #374151;
            margin-bottom: 20px;
            padding: 8px 0;
            border-bottom: 1px auto #f3f4f6;
        }
        .summary-item {
            display: inline-block;
            margin-right: 20px;
        }
        .summary-item span {
            color: #6b7280;
        }
        .summary-item strong {
            color: #111827;
        }

        /* Modern Table Data (Clean Horizontal Lines) */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.data-table th {
            background-color: #1f2937; /* Dark Slate Header */
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            letter-spacing: 0.5px;
            border: none;
        }

        table.data-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        /* Zebra Striping Halus */
        table.data-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Footer Tabel */
        table.data-table tfoot tr {
            background-color: #f3f4f6 !important;
            font-weight: 700;
        }
        table.data-table tfoot td {
            border-top: 1px solid #d1d5db;
            border-bottom: 2px solid #9ca3af;
            padding: 10px;
        }

        /* Helper Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 600; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 10px; }
        
        /* Color Accent */
        .text-primary { color: #111827; }
        .text-emerald { color: #047857; }
        .text-danger { color: #b91c1c; }
        .text-muted { color: #6b7280; font-size: 8px; }

        /* Status Dot Indicator */
        .status-dot {
            font-size: 8px;
            font-weight: 700;
            margin-top: 4px;
            display: block;
            letter-spacing: 0.3px;
        }

        /* Soft Pill Badges */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1 class="title">Laporan Profitabilitas & Margin</h1>
        <p class="subtitle">Unit Bisnis: Toko Bangunan 39 — Audit Dokumen Historis</p>
        <div class="meta-info">
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
            &nbsp;•&nbsp; Segmen: <strong>{{ strtoupper($kategoriPelanggan) }}</strong>
            &nbsp;•&nbsp; Metode: <strong>{{ strtoupper($metodeBayar) }}</strong>
        </div>
    </div>

    <div class="summary-text-bar">
        <div class="summary-item"><span>Total Omset:</span> <strong>Rp {{ number_format($totalOmset, 0, ',', '.') }}</strong></div>
        <div class="summary-item"><span>Total Lunas (CASH):</span> <strong style="color: #047857;">Rp {{ number_format($totalLunas ?? 0, 0, ',', '.') }}</strong></div>
        <div class="summary-item"><span>Total Hutang (TEMPO):</span> <strong style="color: #b45309;">Rp {{ number_format($totalHutang ?? 0, 0, ',', '.') }}</strong></div>
        <div class="summary-item"><span>Total Laba Kotor:</span> <strong>Rp {{ number_format($totalLabaKotor, 0, ',', '.') }}</strong> <strong style="font-weight: normal; color: #6b7280; font-size: 10px;">({{ number_format($averageMarginPercent, 2, ',', '.') }}%)</strong></div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="11%">No. Invoice</th>
                <th width="11%">Tanggal</th>
                <th>Nama Pelanggan</th>
                <th width="8%" class="text-center">Segmen</th>
                <th width="10%">Sales</th>
                <th width="12%" class="text-center">Metode & Status</th>
                <th width="12%" class="text-right">Total Penjualan</th>
                <th width="11%" class="text-right">Total Modal</th>
                <th width="11%" class="text-right">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @php
            $pdfOmset = 0; $pdfModal = 0; $pdfLaba = 0;
            @endphp
            @forelse($transactions as $item)
            @php
            $pdfOmset += (float)$item->total_penjualan;
            $pdfModal += (float)$item->total_modal;
            $pdfLaba += (float)$item->laba_kotor;
            $marginLine = $item->total_penjualan > 0 ? ($item->laba_kotor / $item->total_penjualan) * 100 : 0;
            @endphp
            <tr>
                <td class="text-center" style="color: #9ca3af;">{{ $loop->iteration }}</td>
                <td class="font-bold text-primary">{{ $item->no_invoice }}</td>
                <td style="color: #4b5563;">{{ \Carbon\Carbon::parse($item->tgl_transaksi)->format('d/m/Y') }}</td>
                <td class="font-bold" style="color: #111827;">{{ $item->nama_pelanggan }}</td>
                <td class="text-center">
                    <span class="badge" style="background: #f3f4f6; color: #4b5563;">{{ $item->tipe_pelanggan }}</span>
                </td>
                <td>
                    @if(!empty($item->sales_id) || ($item->nama_sales ?? '-') !== '-')
                        <div style="font-weight: 700; font-family: monospace;">#{{ $item->sales_id ?? '-' }}</div>
                        <div style="font-size: 8px; color: #4b5563;">{{ $item->nama_sales ?? '-' }}</div>
                    @else
                        <span style="color: #9ca3af;">-</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge" style="{{ $item->metode_bayar === 'CASH' ? 'background: #d1fae5; color: #065f46;' : 'background: #fef3c7; color: #92400e;' }}">
                        {{ $item->metode_bayar }}
                    </span>
                    
                    @if($item->metode_bayar === 'CASH' || (int)($item->sisa_piutang ?? 0) === 0)
                        <span class="status-dot" style="color: #047857;">● LUNAS</span>
                    @else
                        <span class="status-dot" style="color: #b91c1c;">● UTANG</span>
                        <div class="font-mono" style="font-size: 7.5px; color: #6b7280; margin-top: 1px;">
                            Bal: {{ number_format((float)$item->sisa_piutang, 0, ',', '.') }}
                        </div>
                    @endif
                </td>
                <td class="text-right font-bold">Rp {{ number_format($item->total_penjualan, 0, ',', '.') }}</td>
                <td class="text-right" style="color: #4b5563;">Rp {{ number_format($item->total_modal, 0, ',', '.') }}</td>
                <td class="text-right">
                    <span class="font-bold {{ $item->laba_kotor >= 0 ? 'text-emerald' : 'text-danger' }}">
                        Rp {{ number_format($item->laba_kotor, 0, ',', '.') }}
                    </span>
                    <div class="text-muted">{{ number_format($marginLine, 2, ',', '.') }}%</div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 30px; color: #6b7280;">
                    Tidak ada catatan transaksi untuk periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-right" style="font-size: 9px; color: #374151;">TOTAL HALAMAN</td>
                <td class="text-right text-primary">Rp {{ number_format($pdfOmset, 0, ',', '.') }}</td>
                <td class="text-right" style="color: #374151;">Rp {{ number_format($pdfModal, 0, ',', '.') }}</td>
                <td class="text-right text-emerald">Rp {{ number_format($pdfLaba, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>