<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $delivery->no_surat_jalan }}</title>
    <style>
    /* Mengatasi Header & Footer Otomatis Browser (URL, Tanggal, dll) */
    @page {
        size: 210mm 240mm;
        margin: 0; /* Tinggi akan disesuaikan lagi oleh script sesuai konten */
    }

    * {
        box-sizing: border-box
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #000000;
        margin: 0;
        padding: 0;
    }

    .page {
        position: relative;
        padding: 8mm 10mm 5mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    /* Page break untuk cetak multi-rangkap */
    .page-break {
        page-break-after: always;
    }

    .header-container {
        width: 100%;
        margin-bottom: 15px;
        position: relative;
    }

    .company-info {
        float: left;
        width: 50%;
    }

    .company-info h1 {
        font-size: 14px;
        font-weight: bold;
        margin: 0 0 4px 0;
    }

    .company-info p {
        margin: 0;
        color: #000000;
        line-height: 1.4;
    }

    .title-box {
        float: right;
        width: 45%;
        text-align: right;
    }

    .title-box h2 {
        border: 1px solid #000000;
        padding: 5px 15px;
        margin: 0 0 6px 0;
        display: inline-block;
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-align: center;
    }

    .title-box .doc-number {
        font-size: 11px;
        font-weight: bold;
    }

    .clear {
        clear: both;
    }

    .main-box {
        border: 1px solid #000000;
        width: 100%;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table td {
        border: 1px solid #000000;
        padding: 6px 8px;
        vertical-align: top;
    }

    .info-group {
        width: 50%;
    }

    .info-row {
        margin-bottom: 4px;
        width: 100%;
    }

    .info-label {
        display: inline-block;
        width: 70px;
    }

    .info-separator {
        display: inline-block;
        width: 10px;
    }

    .info-value {
        display: inline-block;
        font-weight: bold;
    }

    /* Content Table */
    .content-table {
        width: 100%;
        border-collapse: collapse;
    }

    .content-table th,
    .content-table td {
        border: 1px solid #000000;
        padding: 6px 8px;
    }

    .content-table th {
        background: #ffffff;
        text-align: center;
        font-weight: bold;
    }

    .right { text-align: right; }
    .center { text-align: center; }

    .notes-box {
        border-top: 1px solid #000000;
        padding: 8px;
        font-size: 10px;
    }

    .notes-label {
        font-weight: bold;
    }
    
    .notes-label p {
        margin: 3px 0 0 0;
        font-weight: normal;
        line-height: 1.4;
    }

    .sign-container {
        border-top: 1px solid #000000;
        width: 100%;
        text-align: center;
        background: #ffffff;
    }
    
    .sign-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sign-table td {
        width: 33.33%;
        padding: 10px;
        text-align: center;
    }

    .space {
        height: 50px
    }

    .line {
        border-top: 1px solid #000000;
        padding-top: 3px;
        display: inline-block;
        width: 75%;
    }

    .action {
        position: fixed;
        top: 15px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 100
    }

    .action button {
        background: #111827;
        color: white;
        border: 0;
        border-radius: 6px;
        padding: 10px 20px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .badge-copy {
        position: absolute;
        top: 0;
        right: 0;
        border: 1px solid #000000;
        color: #000000;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
    }

    @media print {
        .action {
            display: none
        }
    }
    </style>
</head>

<body>
    @if($autoPrint)
        <div class="action"><button onclick="window.printContentSized()">Cetak Dokumen</button></div>
    @endif
    
    @php($labels = ['Rangkap Toko', 'Rangkap Sopir', 'Rangkap Pembeli'])
    @php($maxCopies = max(1, (int)($copies ?? 3)))
    
    @for($copy = 0; $copy < $maxCopies; $copy++) 
    <div class="page {{ $copy < $maxCopies - 1 ? 'page-break' : '' }}">

        <div class="header-container">
            <div class="company-info">
                <h1>Toko Bangunan 39</h1>
                <p>Alamat: Jl. Pramuka Cikondang kota Sukabumi<br>No. Telp: 085659599869</p>
            </div>
            <div class="title-box">
                <h2>FAKTUR PENJUALAN</h2>
                <div class="doc-number">No. Surat Jalan: {{ $delivery->no_surat_jalan }}</div>
            </div>
            <span class="badge-copy">{{ $labels[$copy] ?? 'Salinan' }}</span>
            <div class="clear"></div>
        </div>

        <div class="main-box">
            <table class="info-table">
                <tr>
                    <td class="info-group">
                        <div class="info-row">
                            <span class="info-label">Pembeli</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $delivery->nama_pelanggan ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Invoice</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $delivery->no_invoice ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Alamat</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $delivery->alamat_tujuan ?: '-' }}</span>
                        </div>
                    </td>
                    <td class="info-group">
                        <div class="info-row">
                            <span class="info-label">Sales / Sopir</span>
                            <span class="info-separator">:</span>
                            <span class="info-value">{{ $delivery->nama_sopir ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="content-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 45%;">Nama Barang</th>
                        <th style="width: 12%;">Qty</th>
                        <th style="width: 13%;">Satuan</th>
                        <th style="width: 12%;" class="right">Harga</th>
                        <th style="width: 13%;" class="right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @php($grandTotal = 0)
                    @foreach($delivery->details as $i => $d)
                        @php($itemTotal = (float)($d->total_belanja ?? 0))
                        @php($grandTotal += $itemTotal)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td>{{ $d->product?->nama_barang ?? $d->kode_barang }}</td>
                            <td class="center">{{ format_qty($d->qty_kirim) }}</td>
                            <td class="center">{{ $d->product?->satuan ?? 'Unit' }}</td>
                            <td class="right">Rp {{ number_format($d->qty_kirim > 0 ? ($itemTotal / $d->qty_kirim) : 0, 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format($itemTotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    
                    <tr style="font-weight: bold; background: #fafafa;">
                        <td colspan="5" class="right" style="font-size: 11px; padding: 8px;">GRAND TOTAL :</td>
                        <td class="right" style="font-size: 11px; padding: 8px; border-left: 1px solid #000000;">
                            Rp {{ number_format($grandTotal, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="notes-box">
                <div class="notes-label">Catatan :
                    <p>1. Pembayaran dapat ditransfer ke rekening Bank BCA: <strong>3770322099</strong> a.n <strong>RIZKY FACHREZA</strong></p>
                </div>
                @if($delivery->catatan)
                    <div style="margin-top: 5px; font-style: italic;">Ket: {{ $delivery->catatan }}</div>
                @endif
            </div>

            <div style="text-align: right; padding: 10px 15px 5px 0; font-size: 11px;">
                Sukabumi, {{ optional($delivery->tgl_terbit)->format('d F Y') ?? date('d F Y') }}
            </div>

            <div class="sign-container">
                <table class="sign-table">
                    <tr>
                        <td>
                            Disiapkan Oleh,
                            <div class="space"></div>
                            <div class="line">Petugas Toko</div>
                        </td>
                        <td>
                            Diserahkan Oleh,
                            <div class="space"></div>
                            <div class="line">{{ $delivery->nama_sopir ?? 'Sopir' }}</div>
                        </td>
                        <td>
                            Diterima Oleh,
                            <div class="space"></div>
                            <div class="line">{{ $delivery->penerima_lokasi ?: 'Pelanggan / Mitra' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
    @endfor

    @unless($nativeDirectPrint ?? false)
    @include('prints.partials.content-sized-page', [
            'target' => '.page',
            'widthMm' => 210,
            'marginMm' => 0,
            'minHeightMm' => 210,
            'maxHeightMm' => 1200,
        ])
@endunless

    @if($autoPrint)
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => { window.printContentSized(); }, 400);
        });
    </script>
    @endif
</body>

</html>