<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Faktur {{ $sale->no_invoice }}</title>
    <style>
        /* =========================
           DOT MATRIX PRINT FIX (EPSON LQ-310)
        ========================= */
        @page {
            size: 210mm 180mm; /* UKURAN KERTAS CUSTOM DOT MATRIX */
            margin: 5mm;       /* MARGIN KECIL AGAR PAS */
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        body {
            font-size: 14px; /* FONT DASAR DIPERBESAR */
            line-height: 1.4;
        }

        /* =========================
           MULTI-COPY SUPPORT
        ========================= */
        .page {
            position: relative;
            width: 100%;
            page-break-inside: avoid;
        }

        .page-break {
            page-break-after: always;
        }

        /* =========================
           HEADER
        ========================= */
        .header-container {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
            display: table;
        }

        .company-info {
            display: table-cell;
            width: 60%;
            vertical-align: bottom;
        }

        .company-info h1 {
            font-size: 22px; /* DIPERBESAR */
            font-weight: bold;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }

        .company-info p {
            margin: 0;
            line-height: 1.4;
            font-size: 14px; /* DIPERBESAR */
            color: #000;
        }

        .title-box {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: bottom;
        }

        .title-box h2 {
            margin: 0 0 5px 0;
            font-size: 18px; /* DIPERBESAR */
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-box .doc-number {
            display: inline-block;
            font-size: 15px; /* DIPERBESAR */
            font-weight: bold;
            border: 1.5px solid #000;
            padding: 4px 10px;
        }

        /* =========================
           INFO SECTION
        ========================= */
        .info-container {
            width: 100%;
            margin-bottom: 15px;
            display: table;
            table-layout: fixed;
        }

        .info-group {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-group + .info-group {
            padding-left: 20px;
        }

        .info-heading {
            font-size: 13px; /* DIPERBESAR */
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .info-row {
            margin-bottom: 4px;
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            width: 90px;
            font-size: 13px; /* DIPERBESAR */
        }

        .info-separator {
            display: table-cell;
            width: 15px;
            text-align: center;
            font-size: 13px;
        }

        .info-value {
            display: table-cell;
            font-weight: bold;
            font-size: 13px; /* DIPERBESAR */
        }

        /* =========================
           CONTENT TABLE
        ========================= */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .content-table thead {
            display: table-header-group;
        }

        .content-table thead th {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px; /* DIPERBESAR */
            padding: 8px;
            border: 1px solid #000;
        }

        .content-table td {
            font-size: 13px; /* DIPERBESAR */
            vertical-align: middle;
            padding: 8px;
            border: 1px solid #000;
        }

        .content-table tr {
            page-break-inside: avoid;
        }

        .right { text-align: right; }
        .center { text-align: center; }
        .left { text-align: left; }

        .total-row td {
            font-weight: bold;
            font-size: 14px; /* DIPERBESAR */
            background: #f0f0f0; /* HIGHLIGHT TOTAL */
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        /* =========================
           NOTES BOX
        ========================= */
        .notes-box {
            width: 100%;
            border: 1px solid #000;
            padding: 10px 14px;
            margin-bottom: 15px;
        }

        .notes-box .notes-label {
            font-weight: bold;
            font-size: 13px; /* DIPERBESAR */
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .notes-box p {
            margin: 4px 0;
            font-size: 13px; /* DIPERBESAR */
        }

        .notes-box .refund-note {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }

        /* =========================
           SIGNATURES
        ========================= */
        .sign-section {
            width: 100%;
            margin-top: 15px;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 13px; /* DIPERBESAR */
        }

        .space {
            height: 60px; /* SPACE UNTUK TTD */
        }

        .line {
            margin: 0 auto;
            padding-top: 5px;
            width: 85%;
            font-size: 13px;
            font-weight: bold;
        }

        /* =========================
           ACTION BUTTON
        ========================= */
        .action {
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
        }

        .action button {
            background: #000;
            color: #fff;
            border: 0;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
        }

        @media print {
            .action { display: none; }
            body { background: #fff; padding: 0; overflow: visible; }
        }
    </style>
</head>

<body>
    @if($autoPrint ?? false)
    <div class="action">
        <button type="button" onclick="window.printContentSized()">Cetak Dokumen</button>
    </div>
    @endif

    @php
    $labels = ['', '', ''];
    $maxCopies = max(1, (int) ($copies ?? 3));
    $customerAddress = $sale->alamat_pelanggan
    ?: $sale->customer?->alamat_lengkap
    ?: '-';
    $salesOrDriver = $sale->sales?->nama_sales ?: '-';
    $netTotal = max(0, (int) $sale->total_belanja - (int) ($sale->refund_total ?? 0));
    @endphp

    @for($copy = 0; $copy < $maxCopies; $copy++) 
    <div class="page {{ $copy < $maxCopies - 1 ? 'page-break' : '' }}">

        <!-- HEADER -->
        <div class="header-container">
            @if(isset($type) && $type === 'khusus')
            <div class="company-info">
                <h1>39</h1>
            </div>
            <div class="title-box">
                <h2 style="text-transform: none;">faktur / surat jalan</h2>
                <div class="doc-number" style="text-transform: none;">no: {{ $sale->no_invoice }}</div>
            </div>
            @else
            <div class="company-info">
                <h1>Toko Bangunan 39</h1>
                <p>
                    Jl. Pramuka Cikondang Kota Sukabumi<br>
                    Telp: 085659599869
                </p>
            </div>
            <div class="title-box">
                <h2>Faktur / Surat Jalan</h2>
                <div class="doc-number">No. {{ $sale->no_invoice }}</div>
            </div>
            @endif
        </div>

        <!-- INFO PELANGGAN & TRANSAKSI -->
        <div class="info-container">
            <div class="info-group">
                <div class="info-heading">Data Pelanggan</div>
                <div class="info-row">
                    <span class="info-label">Pembeli</span>
                    <span class="info-separator">:</span>
                    <span class="info-value">{{ $sale->nama_pelanggan ?: '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Alamat</span>
                    <span class="info-separator">:</span>
                    <span class="info-value">{{ $customerAddress }}</span>
                </div>
            </div>
            <div class="info-group">
                <div class="info-heading">Data Transaksi</div>
                <div class="info-row">
                    <span class="info-label">Sales</span>
                    <span class="info-separator">:</span>
                    <span class="info-value">{{ $salesOrDriver }}</span>
                </div>
                @if(!isset($type) || $type !== 'khusus')
                <div class="info-row">
                    <span class="info-label">Pembayaran</span>
                    <span class="info-separator">:</span>
                    <span class="info-value">{{ $sale->metode_bayar ?: '-' }} / {{ $sale->status ?: '-' }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-separator">:</span>
                    <span class="info-value">{{ optional($sale->tgl_transaksi)->format('d-m-Y') ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- TABEL BARANG -->
        <table class="content-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 40%" class="left">Nama Barang</th>
                    <th style="width: 9%">Qty</th>
                    <th style="width: 12%">Satuan</th>
                    <th style="width: 16%" class="right">Harga</th>
                    <th style="width: 18%" class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->details as $i => $detail)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $detail->product?->nama_barang ?? $detail->kode_barang }}</td>
                    <td class="center">{{ format_qty($detail->qty) }}</td>
                    <td class="center">{{ $detail->product?->satuan ?? 'Unit' }}</td>
                    <td class="right">Rp {{ number_format((int) $detail->harga_jual, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) $detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="5" class="right">GRAND TOTAL</td>
                    <td class="right">Rp {{ number_format($netTotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- CATATAN PEMBAYARAN (BLOK TERPISAH) -->
        @if(!isset($type) || $type !== 'khusus')
        <div class="notes-box">
            <div class="notes-label">Catatan Pembayaran</div>
            <p>Transfer dapat dilakukan ke rekening berikut:</p>
            <p>BCA: <strong>3770322099</strong> a/n <strong>RIZKY FACHREZA</strong></p>
            <p>BRI: <strong>009201112697508</strong> a/n <strong>RIZKY FACHREZA</strong></p>

            @if(($sale->refund_total ?? 0) > 0)
            <p class="refund-note">
                * Nilai refund: <strong>Rp {{ number_format((int) $sale->refund_total, 0, ',', '.') }}</strong>
            </p>
            @endif
        </div>
        @endif

        <!-- TANDA TANGAN (BLOK TERPISAH DI PALING BAWAH) -->
        <div class="sign-section">
            <table class="sign-table">
                <tr>
                    <td>
                        Diterima Oleh,
                        <div class="space"></div>
                        <div class="line">(________________________)</div>
                    </td>
                    <td>
                        Diserahkan Oleh,
                        <div class="space"></div>
                        <div class="line">(________________________)</div>
                    </td>
                    <td>
                        Disiapkan Oleh,
                        <div class="space"></div>
                        <div class="line">Kasir TB 39</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endfor

    @unless($nativeDirectPrint ?? false)
    @include('prints.partials.content-sized-page', [
    'target' => '.page',
    'widthMm' => 210,
    'marginMm' => 5,
    'minHeightMm' => 170,
    'maxHeightMm' => 1200,
    ])
    @endunless

    @if($autoPrint ?? false)
    <script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            if (typeof window.printContentSized === 'function') {
                window.printContentSized();
            } else {
                window.print();
            }
        }, 400);
    });
    </script>
    @endif
</body>

</html>