<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Piutang</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 9px; }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { margin-bottom: 10px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
        th { font-weight: bold; text-align: center; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Detail Piutang per Transaksi</h1>
    <div class="meta">
        <p>Tanggal Export: {{ $generatedAt->format('Y-m-d H:i:s') }}</p>
        <p>Filter Search: {{ $search !== '' ? $search : 'Semua' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID</th>
                <th>No Invoice</th>
                <th>Tanggal Transaksi</th>
                <th>Kode Customer</th>
                <th>Nama Customer</th>
                <th>Alamat</th>
                <th>Total Piutang</th>
                <th>Total Dibayar</th>
                <th>Sisa Piutang</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($debts as $index => $item)
                @php
                    $totalPaid = (int) $item->receivablePayments->sum('nominal');
                    $balance = (int) ($item->sisa_piutang ?? 0);
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $item->id }}</td>
                    <td>{{ $item->no_invoice }}</td>
                    <td>{{ optional($item->tgl_transaksi)->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ optional($item->customer)->kode_cuts ?? '-' }}</td>
                    <td>{{ $item->nama_pelanggan }}</td>
                    <td>{{ optional($item->customer)->alamat_lengkap ?? ($item->alamat_pelanggan ?? '-') }}</td>
                    <td class="right">{{ number_format((int) $item->total_belanja, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($totalPaid, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($balance, 0, ',', '.') }}</td>
                    <td>{{ $balance <= 0 ? 'Lunas' : 'Belum Lunas' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="center">Tidak ada data piutang.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-break"></div>
    <h1>Detail Item per Transaksi Piutang</h1>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Transaksi</th>
                <th>No Invoice</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
                <th>Status Piutang</th>
            </tr>
        </thead>
        <tbody>
            @php $noItem = 1; @endphp
            @foreach ($debts as $item)
                @foreach ($item->details as $detail)
                    @php $balance = (int) ($item->sisa_piutang ?? 0); @endphp
                    <tr>
                        <td class="center">{{ $noItem++ }}</td>
                        <td class="center">{{ $item->id }}</td>
                        <td>{{ $item->no_invoice }}</td>
                        <td>{{ $detail->kode_barang }}</td>
                        <td>{{ optional($detail->product)->nama_barang ?? $detail->kode_barang }}</td>
                        <td>{{ optional($detail->product)->satuan ?? '-' }}</td>
                        <td class="right">{{ number_format((int) $detail->qty, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format((int) $detail->harga_jual, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format((int) $detail->subtotal, 0, ',', '.') }}</td>
                        <td>{{ $balance <= 0 ? 'Lunas' : 'Belum Lunas' }}</td>
                    </tr>
                @endforeach
            @endforeach
            @if ($noItem === 1)
                <tr><td colspan="10" class="center">Tidak ada detail item.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="section-break"></div>
    <h1>Riwayat Pembayaran Cicilan</h1>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Transaksi</th>
                <th>No Invoice</th>
                <th>Nama Customer</th>
                <th>Tanggal Bayar</th>
                <th>Waktu Catat Pembayaran</th>
                <th>Nominal Bayar</th>
                <th>Penerima Kasir</th>
                <th>Sisa Saat Ini</th>
                <th>Status Saat Ini</th>
            </tr>
        </thead>
        <tbody>
            @php $noPay = 1; @endphp
            @foreach ($debts as $item)
                @forelse ($item->receivablePayments as $payment)
                    <tr>
                        <td class="center">{{ $noPay++ }}</td>
                        <td class="center">{{ $item->id }}</td>
                        <td>{{ $item->no_invoice }}</td>
                        <td>{{ $item->nama_pelanggan }}</td>
                        <td>{{ optional($payment->tgl_bayar)->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ optional($payment->created_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td class="right">{{ number_format((int) $payment->nominal, 0, ',', '.') }}</td>
                        <td>{{ $payment->penerima_kasir ?? '-' }}</td>
                        <td class="right">{{ number_format((int) $item->sisa_piutang, 0, ',', '.') }}</td>
                        <td>{{ (int) $item->sisa_piutang <= 0 ? 'Lunas' : 'Belum Lunas' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">{{ $noPay++ }}</td>
                        <td class="center">{{ $item->id }}</td>
                        <td>{{ $item->no_invoice }}</td>
                        <td>{{ $item->nama_pelanggan }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td class="right">0</td>
                        <td>-</td>
                        <td class="right">{{ number_format((int) $item->sisa_piutang, 0, ',', '.') }}</td>
                        <td>{{ (int) $item->sisa_piutang <= 0 ? 'Lunas' : 'Belum Lunas' }}</td>
                    </tr>
                @endforelse
            @endforeach
            @if ($noPay === 1)
                <tr><td colspan="10" class="center">Tidak ada riwayat pembayaran.</td></tr>
            @endif
        </tbody>
    </table>
</body>
</html>
