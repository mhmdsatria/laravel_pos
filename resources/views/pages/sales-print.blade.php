<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota {{ $sale->no_invoice }}</title>
    <style>
        @page { size: 80mm 100mm; margin: 2mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 6px;
            width: 76mm;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 1px; vertical-align: top; }
        th { border-bottom: 1px dashed #000; font-size: 10px; }
        .items td { border-bottom: 1px dotted #999; }
        .no-print-box {
            margin-bottom: 12px;
            background: #f8f8f8;
            padding: 10px;
            text-align: center;
            border: 1px solid #bbb;
            font-family: Arial, sans-serif;
        }
        .btn { padding: 6px 10px; font-weight: bold; cursor: pointer; border-radius: 4px; border: 1px solid #111; background: #fff; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; width: 76mm; }
        }
    </style>
</head>
<body>
    @php
        $refunds = collect($sale->refunds ?? []);
        $refundTotal = (int) ($sale->refund_total ?? 0);
        $netTotal = max(0, (int) $sale->total_belanja - $refundTotal);
    @endphp
    <div class="no-print no-print-box">
        <strong>Modul Cetak Nota TB 39</strong><br><br>
        <button onclick="window.printContentSized()" class="btn">CETAK</button>
        <button onclick="window.close()" class="btn">TUTUP</button>
    </div>

    <main id="print-content">
    <div class="text-center">
        <div class="bold" style="font-size:14px;">TOKO BANGUNAN 39</div>
        <div>Jl Pramuka Cikondang Kota Sukabumi</div>
        <div>No. Telp: 085659599869</div>
    </div>

    <div class="line"></div>

    <table>
        <tr><td>No Struk</td><td class="text-right bold">{{ $sale->no_invoice }}</td></tr>
        <tr><td>Tanggal</td><td class="text-right">{{ optional($sale->tgl_transaksi)->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Pelanggan</td><td class="text-right">{{ $sale->nama_pelanggan ?: 'User Umum' }}</td></tr>
        <!-- <tr><td>Bayar</td><td class="text-right bold">{{ $sale->metode_bayar }}</td></tr>
        @if($sale->metode_bayar === 'TEMPO' && $sale->jatuh_tempo)
        <tr><td>Jatuh Tempo</td><td class="text-right bold">{{ optional($sale->jatuh_tempo)->format('d/m/Y') }}</td></tr>
        @endif -->
        <tr><td>Kasir</td><td class="text-right">{{ $sale->cashier_name }}</td></tr>
    </table>

    <div class="line"></div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:7%;" class="text-center">No</th>
                <th>Nama Item</th>
                <th style="width:20%;" class="text-center">Harga</th>
                <th style="width:10%;" class="text-center">Qty</th>
                <th style="width:23%;" class="text-center">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->nama_barang ?? $item->kode_barang }}</td>
                <td class="text-center">{{ number_format((int) $item->harga_jual, 0, ',', '.') }}</td>
                <td class="text-center">{{ format_qty($item->qty) }}</td>
                <td class="text-center">{{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table>
        <tr class="bold" style="font-size:13px;">
            <td>GRAND TOTAL</td>
            <td class="text-right">Rp {{ number_format((int) $sale->total_belanja, 0, ',', '.') }}</td>
        </tr>

        @if($refundTotal > 0)
        <tr>
            <td>TOTAL REFUND</td>
            <td class="text-right">Rp {{ number_format($refundTotal, 0, ',', '.') }}</td>
        </tr>
        <tr class="bold">
            <td>NETTO</td>
            <td class="text-right">Rp {{ number_format($netTotal, 0, ',', '.') }}</td>
        </tr>
        @endif
        <!-- @if((int) ($sale->sisa_piutang ?? 0) > 0)
        <tr>
            <td>Sisa Piutang</td>
            <td class="text-right">Rp {{ number_format((int) $sale->sisa_piutang, 0, ',', '.') }}</td>
        </tr>
        @endif -->
    </table>

    @if($refundTotal > 0 && $refunds->isNotEmpty())
        <div class="line"></div>
        <div class="bold">NOTA REFUND:</div>
        @foreach($refunds as $refund)
            <div>{{ $refund->no_refund }} - Rp {{ number_format((int) $refund->refund_total, 0, ',', '.') }}</div>
            @foreach($refund->details as $refundDetail)
                <div>- {{ $refundDetail->product?->nama_barang ?? $refundDetail->kode_barang }} x{{ number_format((int) $refundDetail->qty_refund, 0, ',', '.') }}</div>
            @endforeach
        @endforeach
    @endif

    <div class="line"></div>

    <div class="text-center" style="font-size:10px;">
        <br>Terima kasih atas kepercayaan Anda
    </div>

    </main>

    @unless($nativeDirectPrint ?? false)
    @include('prints.partials.content-sized-page', [
            'target' => '#print-content',
            'widthMm' => 80,
            'marginMm' => 2,
            'minHeightMm' => 80,
            'maxHeightMm' => 1000,
        ])
@endunless

    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.printContentSized(); }, 300);
            });
        </script>
    @endif
</body>
</html>
