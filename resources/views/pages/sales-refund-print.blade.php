<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota Refund {{ $refund->no_refund }}</title>
    <style>
        @page { size: 210mm 190mm; margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 13px; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        h1, h2, h3, p { margin: 0; }
        .header { display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 2px solid #111; }
        .brand h1 { font-size: 24px; margin-bottom: 4px; }
        .brand p { font-size: 12px; line-height: 1.4; }
        .document { text-align: right; }
        .document h2 { font-size: 22px; margin-bottom: 5px; }
        .meta { width: 100%; margin: 14px 0; border-collapse: collapse; }
        .meta td { width: 50%; padding: 10px; border: 1px solid #111; vertical-align: top; }
        .line { display: flex; gap: 4px; margin-bottom: 6px; }
        .label { width: 115px; color: #444; }
        .value { flex: 1; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th, .items td { padding: 8px 7px; border: 1px solid #111; font-size: 12px; }
        .items th { text-transform: uppercase; }
        .center { text-align: center; }
        .right { text-align: right; }
        .row-grand { font-weight: 700; }
        .note { margin-top: 14px; padding: 10px; border: 1px solid #111; line-height: 1.5; font-size: 12px; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 48px; margin-top: 46px; text-align: center; font-size: 12px; }
        .sign-space { height: 58px; }
        .sign-line { padding-top: 4px; border-top: 1px solid #111; font-weight: bold; }
        @media print { .no-print { display: none !important; } }
        .no-print { margin-bottom: 12px; text-align: right; }
        .btn { padding: 7px 12px; border: 1px solid #111; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.printContentSized()" class="btn">CETAK</button>
        <button onclick="window.close()" class="btn">TUTUP</button>
    </div>

    <main id="print-content">
    @php
        $sale = $refund->order;
        $address = $sale?->alamat_pelanggan ?: $sale?->customer?->alamat_lengkap ?: '-';
        $netTotal = max(0, (int) ($sale?->total_belanja ?? 0) - (int) ($sale?->refund_total ?? 0));
    @endphp

    <header class="header">
        <div class="brand">
            <h1>TOKO BANGUNAN 39</h1>
            <p>Jl Pramuka Cikondang Kota Sukabumi</p>
            <p>No. Telp: 085659599869</p>
        </div>
        <div class="document">
            <h2>NOTA REFUND / RETUR</h2>
            <strong>{{ $refund->no_refund }}</strong>
        </div>
    </header>

    <table class="meta">
        <tr>
            <td>
                <div class="line"><span class="label">Invoice Asal</span><span>:</span><span class="value"><strong>{{ $sale?->no_invoice ?? '-' }}</strong></span></div>
                <div class="line"><span class="label">Customer</span><span>:</span><span class="value"><strong>{{ $sale?->nama_pelanggan ?: 'User Umum' }}</strong></span></div>
                <div class="line"><span class="label">Alamat</span><span>:</span><span class="value">{{ $address }}</span></div>
            </td>
            <td>
                <div class="line"><span class="label">Tanggal Refund</span><span>:</span><span class="value">{{ optional($refund->refund_at)->format('d M Y H:i') ?: '-' }}</span></div>
                <div class="line"><span class="label">Diproses Oleh</span><span>:</span><span class="value">{{ $refund->refunded_by ?: '-' }}</span></div>
                <div class="line"><span class="label">Total Refund</span><span>:</span><span class="value"><strong>Rp {{ number_format((int) $refund->refund_total, 0, ',', '.') }}</strong></span></div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th>Nama Barang</th>
                <th style="width:10%">Qty</th>
                <th style="width:18%">Harga</th>
                <th style="width:18%">Subtotal Refund</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($refund->details as $index => $detail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $detail->product?->nama_barang ?? $detail->kode_barang }}</strong><br>
                        <small>{{ $detail->kode_barang }}</small>
                    </td>
                    <td class="center">{{ number_format((int) $detail->qty_refund, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) $detail->harga_jual, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) $detail->subtotal_refund, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Tidak ada detail refund.</td></tr>
            @endforelse
            <tr class="row-grand">
                <td colspan="4" class="center">TOTAL REFUND</td>
                <td class="right">Rp {{ number_format((int) $refund->refund_total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="note">
        <strong>Alasan Refund:</strong><br>
        {{ $refund->refund_reason ?: '-' }}
        <br><br>
        <strong>Ringkasan invoice setelah refund:</strong><br>
        Total invoice awal: Rp {{ number_format((int) ($sale?->total_belanja ?? 0), 0, ',', '.') }}<br>
        Akumulasi refund invoice: Rp {{ number_format((int) ($sale?->refund_total ?? 0), 0, ',', '.') }}<br>
        Netto setelah refund: Rp {{ number_format($netTotal, 0, ',', '.') }}
    </div>

    <div class="signatures">
        <div>Diproses Oleh<div class="sign-space"></div><br><br><div class="sign-line">{{ $refund->refunded_by ?: 'Petugas Toko' }}</div></div>
        <div>Diterima Customer<div class="sign-space"></div><br><br><div class="sign-line">(..............................)</div></div>
    </div>

    </main>

    @include('prints.partials.content-sized-page', [
        'target' => '#print-content',
        'widthMm' => 210,
        'marginMm' => 5,
        'minHeightMm' => 170,
        'maxHeightMm' => 1200,
    ])

    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() { window.printContentSized(); }, 500);
            });
        </script>
    @endif
</body>
</html>
