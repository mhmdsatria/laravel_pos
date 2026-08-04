<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Barang</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #000; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; text-align: center; }
        .meta { text-align: center; margin-bottom: 16px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px 7px; }
        th { font-weight: bold; text-align: center; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan Stok Barang</h1>
    <div class="meta">Dicetak: {{ $printedAt ?? now()->format('d/m/Y H:i:s') }}</div>

    <table>
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Stok Saat Ini</th>
                <th>Status Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['kode_barang'] }}</td>
                    <td>{{ $row['nama_barang'] }}</td>
                    <td class="center">{{ $row['satuan'] }}</td>
                    <td class="center">{{ is_numeric($row['stok_saat_ini']) ? format_qty($row['stok_saat_ini']) : $row['stok_saat_ini'] }}</td>
                    <td class="center">{{ $row['status_stok'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center">Tidak ada data stok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
