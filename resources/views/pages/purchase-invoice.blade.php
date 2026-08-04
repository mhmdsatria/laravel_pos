<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pembelian {{ $purchase->no_invoice }}</title>
    <style>
        @page { size: 210mm 190mm; margin: 5mm; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #212121; margin: 0; padding: 30px; background: #fff; line-height: 1.5; font-size: 12px; }
        .invoice-container { max-width: 750px; margin: 0 auto; position: relative; }
        
        /* Brand Header */
        .brand-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .brand-name { font-size: 24px; font-weight: 800; color: #03ac0e; letter-spacing: -0.5px; } /* Warna hijau khas e-commerce jika diperlukan, atau ganti #111 */
        .invoice-title-right { text-align: right; }
        .invoice-title-right .main-title { font-size: 14px; font-weight: bold; color: #212121; letter-spacing: 1px; }
        .invoice-title-right .invoice-number { font-size: 11px; color: #03ac0e; font-weight: bold; margin-top: 2px; }

        /* Meta Grid Layout */
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .meta-block .title-label { font-size: 11px; font-weight: bold; color: #31353B; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; vertical-align: top; font-size: 12px; color: #212121; }
        .meta-table td.label { width: 32%; color: #6D7588; }
        .meta-table td.separator { width: 3%; color: #6D7588; }
        .meta-table td.value { width: 65%; font-weight: 500; }

        /* Separator Line Lembut */
        .divider { border-top: 1px solid #E5E7E9; margin: 20px 0; }
        .divider-thick { border-top: 2px solid #31353B; margin-top: 10px; }

        /* Tabel Produk Utama */
        table.items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items-table th { padding: 8px 0; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 11px; color: #31353B; border-bottom: 2px solid #31353B; }
        table.items-table td { padding: 12px 0; border-bottom: 1px solid #F0F1F2; vertical-align: top; font-size: 12px; }
        
        .product-name { font-weight: bold; color: #03ac0e; font-size: 12px; }
        .product-meta { font-size: 11px; color: #6D7588; margin-top: 3px; }

        /* Alignment */
        .right { text-align: right; }
        .center { text-align: center; }

        /* Sisi Perhitungan Finansial */
        .bottom-layout { display: flex; justify-content: space-between; margin-top: 15px; gap: 30px; }
        .bottom-left { width: 50%; }
        .bottom-right { width: 50%; }

        table.summary-table { width: 100%; border-collapse: collapse; }
        table.summary-table td { padding: 5px 0; font-size: 12px; color: #212121; }
        table.summary-table td.label { color: #212121; text-transform: uppercase; font-size: 11px; font-weight: bold; }
        table.summary-table tr.total-row td { font-weight: bold; font-size: 13px; border-top: 1px dashed #E5E7E9; padding-top: 10px; margin-top: 5px; }
        table.summary-table tr.grand-total-row td { font-weight: bold; font-size: 14px; border-top: 1px solid #31353B; padding-top: 10px; }

        /* Watermark Status (Lunas / Tempo) */
        .status-watermark { position: absolute; top: 35%; left: 1%; transform: rotate(-15deg); font-size: 70px; font-weight: 900; letter-spacing: 8px; text-transform: uppercase; opacity: 0.06; pointer-events: none; width: 100%; text-align: center; z-index: 0; }
        .watermark-paid { color: #03ac0e; }
        .watermark-unpaid { color: #ef1414; }

        /* Footer Keterangan Sistem */
        .system-footer { margin-top: 25px; font-size: 11px; color: #6D7588; line-height: 1.6; }
        .system-footer strong { color: #03ac0e; }

        /* Tombol Aksi */
        .actions { max-width: 750px; margin: 20px auto 0; text-align: right; }
        button { border: 1px solid #E5E7E9; background: #fff; color: #31353B; padding: 8px 16px; font-size: 12px; font-weight: bold; cursor: pointer; border-radius: 8px; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        button:hover { background: #F0F1F2; }
        
        @media print { 
            body { padding: 0; font-size: 11px; color: #212121; } 
            .invoice-container { max-width: none; width: 100%; } 
            .actions { display: none; }
            table.items-table th { border-bottom: 2px solid #31353B !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.items-table tr, .bottom-layout, .system-footer { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

@php
    $isLunas = $purchase->payment_status === 'Lunas';
@endphp

<div class="invoice-container">
    
    <div class="status-watermark {{ $isLunas ? 'watermark-paid' : 'watermark-unpaid' }}">
        {{ $purchase->payment_status }}
    </div>

    <div class="brand-section">
        <div class="brand-name">TOKO BANGUNAN 39</div> <div class="invoice-title-right">
            <div class="main-title">INVOICE</div>
            <div class="invoice-number">{{ $purchase->no_invoice }}</div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-block">
            <div class="title-label">Diterbitkan Atas Nama</div>
            <table class="meta-table">
                <tr>
                    <td class="label">Penerima</td>
                    <td class="separator">:</td>
                    <td class="value">Gudang Utama Koperasi</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Beli</td>
                    <td class="separator">:</td>
                    <td class="value">{{ optional($purchase->tgl_pembelian)->format('d M Y') ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Status Nota</td>
                    <td class="separator">:</td>
                    <td class="value" style="color: {{ $isLunas ? '#03ac0e' : '#b45309' }}; font-weight: bold;">
                        {{ $purchase->payment_status }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-block">
            <div class="title-label">Untuk / Asal Goods</div>
            <table class="meta-table">
                <tr>
                    <td class="label">Supplier</td>
                    <td class="separator">:</td>
                    <td class="value" style="font-weight: bold;">{{ $purchase->supplier?->nama_supplier ?? '-' }}</td>
                </tr>
                <tr>
                </tr>
                <tr>
                    <td class="label">Kode Supplier</td>
                    <td class="separator">:</td>
                    <td class="value">SUP-{{ str_pad((string) ((int) ($purchase->supplier?->id ?? $purchase->supplier_id)), 4, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <td class="label">Kontak / Alamat</td>
                    <td class="separator">:</td>
                    <td class="value">
                        @if($purchase->supplier?->nama_pic) {{ $purchase->supplier->nama_pic }} @endif
                        @if($purchase->supplier?->no_hp) ({{ $purchase->supplier->no_hp }})<br> @endif
                        @if($purchase->supplier?->alamat) <span style="color:#6D7588; font-size:11px;">{{ $purchase->supplier->alamat }}</span> @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 55%;">Info Produk</th>
                <th class="center" style="width: 10%;">Jumlah</th>
                <th class="right" style="width: 15%;">Harga Satuan</th>
                <th class="right" style="width: 20%;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->details as $index => $detail)
                <tr>
                    <td>
                        <div class="product-name">{{ $detail->product?->nama_barang ?? $detail->kode_barang }}</div>
                        <div class="product-meta">SKU: {{ $detail->kode_barang }} @if($detail->product?->satuan) | Satuan: {{ $detail->product->satuan }} @endif</div>
                    </td>
                    <td class="center" style="font-weight: 500;">{{ format_qty($detail->qty) }}</td>
                    <td class="right">Rp{{ number_format((int) $detail->harga_beli, 0, ',', '.') }}</td>
                    <td class="right" style="font-weight: 500;">Rp{{ number_format((int) $detail->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-layout">
        <div class="bottom-left">
            @if (!empty($purchase->note))
                <div class="title-label" style="margin-top: 10px; font-size: 10px; color: #6D7588;">Catatan Pembelian:</div>
                <div style="font-size: 11px; color: #31353B; white-space: pre-line; font-style: italic;">"{{ $purchase->note }}"</div>
            @endif

            @if ($purchase->payments->count())
                <div class="title-label" style="margin-top: 15px; font-size: 10px; color: #6D7588; border-bottom: 1px dashed #E5E7E9; padding-bottom: 2px;">Metode / Histori Cicilan:</div>
                <div style="font-size: 11px; color: #31353B; line-height: 1.6;">
                    @foreach ($purchase->payments as $pIndex => $payment)
                        • {{ optional($payment->payment_date)->format('d/m/Y') }} — {{ $payment->payment_method ?? 'Tunai' }} 
                        <strong>(Rp{{ number_format((int) $payment->amount, 0, ',', '.') }})</strong><br>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bottom-right">
            <table class="summary-table">
                <tr>
                    <td class="label" style="font-weight: normal; color: #31353B;">Subtotal Harga Barang</td>
                    <td class="right" style="font-weight: bold;">Rp{{ number_format((int) $purchase->total_harga, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Total Belanja</td>
                    <td class="right">Rp{{ number_format((int) $purchase->total_harga, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label" style="font-weight: normal; color: #6D7588;">Total Paid (Sudah Dibayar)</td>
                    <td class="right" style="color: #03ac0e; font-weight: bold;">-Rp{{ number_format((int) $purchase->paid_total, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total-row">
                    <td class="label" style="font-size: 12px;">Total Sisa Tagihan</td>
                    <td class="right" style="font-size: 13px; color: #212121;">Rp{{ number_format((int) $purchase->remaining_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="system-footer">
        <div class="divider"></div>
        Invoice ini sah dan diproses otomatis oleh sistem komputer manajemen stok.<br>
        Silakan hubungi <strong>Koperasi Care</strong> atau bagian finansial apabila memerlukan bantuan verifikasi.
        <div style="float: right; font-style: italic;" class="muted">Terakhir diupdate: {{ now()->format('d M Y H:i') }} WIB</div>
    </div>

</div>

<div class="actions">
    <button onclick="window.printContentSized()">Cetak Invoice Resmi</button>
</div>

@include('prints.partials.content-sized-page', [
    'target' => '.invoice-container',
    'widthMm' => 210,
    'marginMm' => 5,
    'minHeightMm' => 170,
    'maxHeightMm' => 1200,
])

</body>
</html>